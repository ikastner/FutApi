<?php


namespace App\Entity;

use App\Repository\UserPackPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPackPlayerRepository::class)]
class UserPackPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pack $pack = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SoccerPlayers $player = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $obtainedAt = null;

    public function __construct()
    {
        $this->obtainedAt = new \DateTimeImmutable();
    }

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

    public function getPack(): ?Pack
    {
        return $this->pack;
    }

    public function setPack(?Pack $pack): static
    {
        $this->pack = $pack;
        return $this;
    }

    public function getPlayer(): ?SoccerPlayers
    {
        return $this->player;
    }

    public function setPlayer(?SoccerPlayers $player): static
    {
        $this->player = $player;
        return $this;
    }

    public function getObtainedAt(): ?\DateTimeImmutable
    {
        return $this->obtainedAt;
    }

    public function setObtainedAt(\DateTimeImmutable $obtainedAt): static
    {
        $this->obtainedAt = $obtainedAt;
        return $this;
    }
}