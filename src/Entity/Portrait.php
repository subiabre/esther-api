<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Repository\PortraitRepository;
use App\State\PortraitStateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[API\GetCollection()]
#[API\Post(processor: PortraitStateProcessor::class)]
#[API\Get()]
#[API\Put()]
#[API\Delete()]
#[API\Patch()]
#[ORM\Entity(repositoryClass: PortraitRepository::class)]
class Portrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $src = null;

    #[ORM\Column]
    private ?int $width = null;

    #[ORM\Column]
    private ?int $height = null;

    #[ORM\Column]
    private ?int $offsetX = null;

    #[ORM\Column]
    private ?int $offsetY = null;

    #[API\ApiProperty(writable: false)]
    #[ORM\ManyToOne(inversedBy: 'portraits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Image $image = null;

    #[ORM\ManyToOne(inversedBy: 'portraits')]
    private ?Person $person = null;

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

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getOffsetX(): ?int
    {
        return $this->offsetX;
    }

    public function setOffsetX(int $offsetX): static
    {
        $this->offsetX = $offsetX;

        return $this;
    }

    public function getOffsetY(): ?int
    {
        return $this->offsetY;
    }

    public function setOffsetY(int $offsetY): static
    {
        $this->offsetY = $offsetY;

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }
}
