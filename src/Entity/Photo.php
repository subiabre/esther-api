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
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Photos are displayable, filterable and sortable collections of related Images.
 * e.g. Digital scans of the two sides of an analogic photo.
 */
#[Gedmo\Loggable()]
#[API\GetCollection(security: "is_granted('ROLE_USER')")]
#[API\Post(security: "is_granted('ROLE_USER')")]
#[API\Get(security: "is_granted('SCOPE_VIEW', object)")]
#[API\Put(security: "is_granted('ROLE_USER')")]
#[API\Delete(security: "is_granted('ROLE_USER')")]
#[API\Patch(security: "is_granted('ROLE_USER')")]
#[ORM\Entity(repositoryClass: PhotoRepository::class)]
class Photo implements Loggable
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
    #[Gedmo\Versioned]
    #[Assert\Valid()]
    #[Assert\NotBlank()]
    #[API\ApiFilter(PhotoDateOrderFilter::class)]
    #[API\ApiFilter(PhotoDateRangeFilter::class)]
    #[ORM\Embedded(class: PhotoDateRange::class)]
    private ?PhotoDateRange $date = null;

    /**
     * A breakdown of the address and the different components of the Photo's location.
     */
    #[Gedmo\Versioned]
    #[Assert\Valid()]
    #[API\ApiFilter(PhotoAddressKnownFilter::class)]
    #[API\ApiFilter(PhotoAddressComponentsFilter::class)]
    #[ORM\Embedded(class: PhotoAddress::class)]
    private ?PhotoAddress $address = null;

    /**
     * Scopes hold the relationship between an User's role and their access to a Photo.
     */
    #[API\ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ORM\OneToMany(
        targetEntity: PhotoScope::class,
        mappedBy: 'photo',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $scopes;

    #[Assert\NotBlank()]
    #[Assert\Count(min: 1)]
    #[API\ApiFilter(
        SearchFilter::class,
        properties: ['images.alt' => 'partial', 'images.portraits.person'],
    )]
    #[ORM\OneToMany(
        targetEntity: Image::class,
        mappedBy: 'photo',
        cascade: ['persist']
    )]
    private Collection $images;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
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
     * @return Collection<int, PhotoScope>
     */
    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function addScope(PhotoScope $scope): static
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
            $scope->setPhoto($this);
        }

        return $this;
    }

    public function removeScope(PhotoScope $scope): static
    {
        if ($this->scopes->removeElement($scope)) {
            // set the owning side to null (unless already changed)
            if ($scope->getPhoto() === $this) {
                $scope->setPhoto(null);
            }
        }

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
}
