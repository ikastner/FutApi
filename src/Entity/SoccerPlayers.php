<?php

namespace App\Entity;

use App\Repository\SoccerPlayersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
#[ORM\Entity(repositoryClass: SoccerPlayersRepository::class)]
#[ApiResource]
class SoccerPlayers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $club = null;

    #[ORM\Column(length: 255)]
    private ?string $nation = null;

    #[ORM\Column]
    private ?int $rating = null;

    #[ORM\Column(length: 255)]
    private ?string $rarity = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?float $rate = null;

    /**
     * @var Collection<int, Pack>
     */
    #[ORM\ManyToMany(targetEntity: Pack::class, mappedBy: 'players')]
    private Collection $playersPack;

    public function __construct()
    {
        $this->playersPack = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getClub(): ?string
    {
        return $this->club;
    }

    public function setClub(string $club): static
    {
        $this->club = $club;

        return $this;
    }

    public function getNation(): ?string
    {
        return $this->nation;
    }

    public function setNation(string $nation): static
    {
        $this->nation = $nation;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return Collection<int, Pack>
     */
    public function getPlayersPack(): Collection
    {
        return $this->playersPack;
    }

    public function addPlayersPack(Pack $playersPack): static
    {
        if (!$this->playersPack->contains($playersPack)) {
            $this->playersPack->add($playersPack);
            $playersPack->addPlayer($this);
        }

        return $this;
    }

    public function removePlayersPack(Pack $playersPack): static
    {
        if ($this->playersPack->removeElement($playersPack)) {
            $playersPack->removePlayer($this);
        }

        return $this;
    }
}
