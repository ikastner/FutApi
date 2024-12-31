<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $username = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $apiToken = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $credits = 1000;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserPackPlayer::class)]
    private Collection $userPackPlayers;

    public function __construct()
    {
        $this->userPackPlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(?int $credits): void
    {
        $this->credits = $credits;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return Collection<int, UserPackPlayer>
     */
    public function getUserPackPlayers(): Collection
    {
        return $this->userPackPlayers;
    }

    public function addUserPackPlayer(UserPackPlayer $userPackPlayer): static
    {
        if (!$this->userPackPlayers->contains($userPackPlayer)) {
            $this->userPackPlayers->add($userPackPlayer);
            $userPackPlayer->setUser($this);
        }
        return $this;
    }

    public function removeUserPackPlayer(UserPackPlayer $userPackPlayer): static
    {
        if ($this->userPackPlayers->removeElement($userPackPlayer)) {
            if ($userPackPlayer->getUser() === $this) {
                $userPackPlayer->setUser(null);
            }
        }
        return $this;
    }
}