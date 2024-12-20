<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata as API;
use App\Repository\PersonRepository;
use App\State\PeopleStateProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[API\ApiResource(provider: PeopleStateProvider::class)]
#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[API\ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Portrait::class, mappedBy: 'person')]
    private Collection $portraits;

    public function __construct()
    {
        $this->portraits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Portrait>
     */
    public function getPortraits(): Collection
    {
        return $this->portraits;
    }

    public function addPortrait(Portrait $portrait): static
    {
        if (!$this->portraits->contains($portrait)) {
            $this->portraits->add($portrait);
            $portrait->setPerson($this);
        }

        return $this;
    }

    public function removePortrait(Portrait $portrait): static
    {
        if ($this->portraits->removeElement($portrait)) {
            // set the owning side to null (unless already changed)
            if ($portrait->getPerson() === $this) {
                $portrait->setPerson(null);
            }
        }

        return $this;
    }
}
