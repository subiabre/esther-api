<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Entity\Trait\TimestampableCreation;
use App\Entity\Trait\TimestampableUpdation;
use App\Repository\ImageRepository;
use App\State\ImageStateProcessor;
use App\Validator\ImageFile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Images exist in a 1:1 relation with an image file stored somewhere.
 */
#[UniqueEntity(fields: ['src'])]
#[API\GetCollection(security: "is_granted('ROLE_USER')")]
#[API\Post(security: "is_granted('ROLE_USER')", processor: ImageStateProcessor::class)]
#[API\Get(security: "is_granted('ROLE_USER')")]
#[API\Put(security: "is_granted('ROLE_USER')")]
#[API\Delete(security: "is_granted('ROLE_USER')")]
#[API\Patch(security: "is_granted('ROLE_USER')")]
#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Gedmo\Loggable()]
class Image
{
    use TimestampableCreation;
    use TimestampableUpdation;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Fully qualified path to the file.
     */
    #[Assert\NotBlank()]
    #[Assert\Url(message: 'The url {{ value }} is not a valid url')]
    #[ImageFile()]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $src = null;

    /**
     * A descriptive text of the image,
     * also used as the alternative text for the (non) displayed image.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Versioned()]
    private ?string $alt = null;

    /**
     * A downscaled version of the Image's file, stored elsewhere.
     */
    #[API\ApiProperty(writable: false)]
    #[ORM\Embedded(class: ImageThumb::class)]
    private ?ImageThumb $thumb;

    #[API\ApiProperty(readableLink: true)]
    #[ORM\OneToMany(targetEntity: Portrait::class, mappedBy: 'image', orphanRemoval: true)]
    private Collection $portraits;

    /**
     * ImageMetadata holds a mix of information sourced from an Image's file.
     */
    #[API\ApiProperty(writable: false)]
    #[ORM\Embedded(class: ImageMetadata::class)]
    private ?ImageMetadata $metadata = null;

    /**
     * The Photo to which this Image belongs.
     */
    #[API\ApiProperty(writable: false)]
    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?Photo $photo = null;

    public function __construct()
    {
        $this->portraits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function encodeSrc(string $src): string
    {
        $filename = \pathinfo($src)['filename'];

        return \str_replace($filename, \urlencode($filename), $src);
    }

    public static function decodeSrc(string $src): string
    {
        $filename = \pathinfo($src)['filename'];

        return \str_replace($filename, \urldecode($filename), $src);
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }

    public function setSrc(string $src): static
    {
        $this->src = self::encodeSrc($src);

        return $this;
    }

    public function getFilename(): ?string
    {
        $src = self::decodeSrc($this->src);

        return \pathinfo($src)['filename'];
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

    public function getThumb(): ?ImageThumb
    {
        return $this->thumb;
    }

    public function setThumb(ImageThumb $thumb): static
    {
        $this->thumb = $thumb;

        return $this;
    }

    /**
     * @return Collection<int, Portrait>
     */
    public function getPortraits(): Collection
    {
        return $this->portraits;
    }

    /**
     * @param Collection<int, Portrait>
     */
    public function setPortraits(Collection $portraits): static
    {
        $this->portraits = $portraits;

        return $this;
    }

    public function addPortrait(Portrait $portrait): static
    {
        if (!$this->portraits->contains($portrait)) {
            $this->portraits->add($portrait);
            $portrait->setImage($this);
        }

        return $this;
    }

    public function removePortrait(Portrait $portrait): static
    {
        if ($this->portraits->removeElement($portrait)) {
            // set the owning side to null (unless already changed)
            if ($portrait->getImage() === $this) {
                $portrait->setImage(null);
            }
        }

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

    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    public function setPhoto(?Photo $photo): static
    {
        $this->photo = $photo;

        return $this;
    }
}
