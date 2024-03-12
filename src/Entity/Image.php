<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Entity\Trait\TimestampableCreation;
use App\Entity\Trait\TimestampableUpdation;
use App\Repository\ImageRepository;
use App\State\ImageStateProcessor;
use App\Validator\ImageFile;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

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

    #[Assert\Url()]
    #[ImageFile()]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $src = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $alt = null;

    #[API\ApiProperty(writable: false)]
    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?Photo $photo = null;

    #[API\ApiProperty(writable: false)]
    #[ORM\Embedded(class: ImageThumb::class)]
    private ?ImageThumb $thumb;

    #[API\ApiProperty(writable: false)]
    #[ORM\Embedded(class: ImageMetadata::class)]
    private ?ImageMetadata $metadata = null;

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

    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    public function setPhoto(?Photo $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getThumb(): ?ImageThumb
    {
        return $this->thumb;
    }

    public function setThumb(ImageThumb $thumb): static
    {
        $this->thumb = $thumb;

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
}
