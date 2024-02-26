<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Entity\Interface\UserOwnedInterface;
use App\Entity\Trait\TimestampableCreation;
use App\Entity\Trait\TimestampableUpdation;
use App\Repository\UserRepository;
use App\State\UserPasswordProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[API\GetCollection(security: "is_granted('ROLE_USER')")]
#[API\Post(security: "is_granted('PUBLIC_ACCESS')", processor: UserPasswordProcessor::class)]
#[API\Get(security: "is_granted('ROLE_USER')")]
#[API\Put(security: "is_granted('USER_EDIT', object)")]
#[API\Delete(security: "is_granted('USER_EDIT', object)")]
#[API\Patch(security: "is_granted('USER_EDIT', object)")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, UserOwnedInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableCreation;
    use TimestampableUpdation;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Email()]
    #[Assert\NotBlank()]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[API\ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[API\ApiProperty(writable: false, readable: false)]
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 12)]
    #[API\ApiProperty(readable: false)]
    #[SerializedName('password')]
    private ?string $plainPassword = null;

    #[API\ApiProperty(writable: false, security: "is_granted('USER_EDIT', object)")]
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $sessions;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function isOwnedBy(User $user): bool
    {
        return $user->getUserIdentifier() === $this->getUserIdentifier();
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function hasRoles(array $roles): bool
    {
        return 0 < count(array_intersect($this->getRoles(), $roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setUser($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getUser() === $this) {
                $session->setUser(null);
            }
        }

        return $this;
    }
}
