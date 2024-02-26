<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Entity\Trait\TimestampableCreation;
use App\Entity\Trait\TimestampableUpdation;
use App\Filter\PhotoDateRangeFilter;
use App\Repository\PhotoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

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

    #[Assert\NotBlank()]
    #[Assert\Count(min: 1)]
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'photo')]
    private Collection $images;

    #[Gedmo\Versioned]
    #[Assert\Valid()]
    #[Assert\NotBlank()]
    #[API\ApiFilter(PhotoDateRangeFilter::class)]
    #[ORM\Embedded(class: PhotoDateRange::class)]
    private ?PhotoDateRange $date = null;

    #[API\ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ORM\OneToMany(
        targetEntity: PhotoScope::class,
        mappedBy: 'photo',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $scopes;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->scopes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
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

    public function getDate(): ?PhotoDateRange
    {
        return $this->date;
    }

    public function setDate(PhotoDateRange $date): static
    {
        $this->date = $date;

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
}
