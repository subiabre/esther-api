<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Dto\SessionAuthenticationDto;
use App\Entity\Interface\UserOwnedInterface;
use App\Entity\Trait\TimestampableCreation;
use App\Repository\SessionRepository;
use App\State\SessionAuthenticationProcessor;
use Doctrine\ORM\Mapping as ORM;

#[API\Post(
    security: "is_granted('PUBLIC_ACCESS')",
    input: SessionAuthenticationDto::class,
    processor: SessionAuthenticationProcessor::class
)]
#[API\Get(security: "is_granted('SESSION_VIEW', object)")]
#[API\Delete(security: "is_granted('SESSION_EDIT', object)")]
#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session implements UserOwnedInterface
{
    use TimestampableCreation;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 128)]
    private ?string $token = null;

    #[ORM\Embedded(class: SessionRequest::class)]
    private ?SessionRequest $request = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isOwnedBy(User $user): bool
    {
        return $user->getUserIdentifier() === $this->user?->getUserIdentifier();
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getRequest(): ?SessionRequest
    {
        return $this->request;
    }

    public function setRequest(SessionRequest $request): static
    {
        $this->request = $request;

        return $this;
    }
}
