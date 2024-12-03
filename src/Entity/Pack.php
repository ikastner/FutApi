<?php

namespace App\Entity;

use App\Repository\PackRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PackRepository::class)]
class Pack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $price = null;

    /**
     * @var Collection<int, SoccerPlayers>
     */
    #[ORM\ManyToMany(targetEntity: SoccerPlayers::class, inversedBy: 'playersPack')]
    private Collection $players;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    public function __construct()
    {
        $this->players = new ArrayCollection();
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

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, SoccerPlayers>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        // Définir le prix en fonction du type de pack
        switch ($type) {
            case 'Bronze':
                $this->price = 500;
                break;
            case 'Argent':
                $this->price = 2500;
                break;
            case 'Or':
                $this->price = 7500;
                break;
            case 'Icône':
                $this->price = 15000;
                break;
            default:
                throw new \InvalidArgumentException("Type de pack non valide : $type");
        }

        return $this;
    }

    public function addPlayer(SoccerPlayers $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
        }

        return $this;
    }

    public function removePlayer(SoccerPlayers $player): static
    {
        $this->players->removeElement($player);

        return $this;
    }
}
