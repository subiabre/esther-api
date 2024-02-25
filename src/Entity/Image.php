<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Entity\Trait\TimestampableCreation;
use App\Entity\Trait\TimestampableUpdation;
use App\Repository\ImageRepository;
use App\State\ImageStateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['src'])]
#[API\GetCollection(security: "is_granted('ROLE_USER')")]
#[API\Post(security: "is_granted('ROLE_USER')", processor: ImageStateProcessor::class)]
#[API\Get(security: "is_granted('ROLE_USER')")]
#[API\Put(security: "is_granted('ROLE_USER')")]
#[API\Delete(security: "is_granted('ROLE_USER')")]
#[API\Patch(security: "is_granted('ROLE_USER')")]
#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    use TimestampableCreation;
    use TimestampableUpdation;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $src = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $alt = null;

    #[API\ApiProperty(writable: false)]
    #[ORM\Embedded(class: ImageMetadata::class)]
    private ?ImageMetadata $metadata = null;

    #[API\ApiProperty(writable: false)]
    #[ORM\OneToMany(targetEntity: ImageThumb::class, mappedBy: 'image', cascade: ['persist'])]
    private Collection $thumbs;

    public function __construct()
    {
        $this->thumbs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }

    public function setSrc(string $src): static
    {
        $this->src = $src;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getMetadata(): ?ImageMetadata
    {
        return $this->metadata;
    }

    public function setMetadata(ImageMetadata $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return Collection<int, ImageThumb>
     */
    public function getThumbs(): Collection
    {
        return $this->thumbs;
    }

    public function addThumb(ImageThumb $thumb): static
    {
        if (!$this->thumbs->contains($thumb)) {
            $this->thumbs->add($thumb);
            $thumb->setImage($this);
        }

        return $this;
    }

    public function removeThumb(ImageThumb $thumb): static
    {
        if ($this->thumbs->removeElement($thumb)) {
            // set the owning side to null (unless already changed)
            if ($thumb->getImage() === $this) {
                $thumb->setImage(null);
            }
        }

        return $this;
    }
}
