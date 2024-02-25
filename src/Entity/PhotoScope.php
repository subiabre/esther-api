<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Repository\PhotoScopeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoScopeRepository::class)]
class PhotoScope extends Scope
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[API\ApiProperty(writable: false, readable: false)]
    #[ORM\ManyToOne(inversedBy: 'scopes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Photo $photo = null;

    public function getId(): ?int
    {
        return $this->id;
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
