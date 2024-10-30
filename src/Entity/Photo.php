<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata as API;
use App\Entity\Trait\TimestampableCreation;
use App\Entity\Trait\TimestampableUpdation;
use App\Filter\PhotoAddressComponentsFilter;
use App\Filter\PhotoAddressKnownFilter;
use App\Filter\PhotoDateOrderFilter;
use App\Filter\PhotoDateRangeFilter;
use App\Repository\PhotoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Photos are displayable, filterable and sortable collections of related Images.
 * e.g. Digital scans of the two sides of an analogic photo.
 */
#[API\GetCollection(security: "is_granted('ROLE_USER')")]
#[API\Post(security: "is_granted('ROLE_USER')")]
#[API\Get(security: "is_granted('PHOTO_VIEW', object)")]
#[API\Put(security: "is_granted('ROLE_USER')")]
#[API\Delete(security: "is_granted('ROLE_USER')")]
#[API\Patch(security: "is_granted('ROLE_USER')")]
#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[Gedmo\Loggable()]
class Photo
{
    use TimestampableCreation;
    use TimestampableUpdation;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Photos are dated approximately between a range of dates.
     */
    #[Assert\Valid()]
    #[Assert\NotBlank()]
    #[API\ApiFilter(PhotoDateOrderFilter::class)]
    #[API\ApiFilter(PhotoDateRangeFilter::class)]
    #[ORM\Embedded(class: PhotoDateRange::class)]
    #[Gedmo\Versioned()]
    private ?PhotoDateRange $date = null;

    /**
     * A breakdown of the address and the different components of the Photo's location.
     */
    #[Assert\Valid()]
    #[API\ApiFilter(PhotoAddressKnownFilter::class)]
    #[API\ApiFilter(PhotoAddressComponentsFilter::class)]
    #[ORM\Embedded(class: PhotoAddress::class)]
    #[Gedmo\Versioned()]
    private ?PhotoAddress $address = null;

    #[Assert\NotBlank()]
    #[Assert\Count(min: 1)]
    #[API\ApiFilter(
        SearchFilter::class,
        properties: ['images.alt' => 'partial', 'images.portraits.person'],
    )]
    #[ORM\OrderBy(['src' => 'ASC'])]
    #[ORM\OneToMany(
        targetEntity: Image::class,
        mappedBy: 'photo',
        cascade: ['persist']
    )]
    private Collection $images;

    #[API\ApiProperty(security: 'is_granted("ROLE_ADMIN")')]
    #[ORM\Column]
    #[Gedmo\Versioned()]
    private array $roles = [];

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?PhotoDateRange
    {
        return $this->date;
    }

    public function setDate(PhotoDateRange $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getAddress(): ?PhotoAddress
    {
        return $this->address;
    }

    public function setAddress(PhotoAddress $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function setImages(Collection $images): static
    {
        $this->images = $images;

        return $this;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setPhoto($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getPhoto() === $this) {
                $image->setPhoto(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        return $roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }
}
