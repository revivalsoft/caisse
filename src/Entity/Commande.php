<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Commande
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TableRestaurant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?TableRestaurant $table = null;

    #[ORM\ManyToMany(targetEntity: Produit::class)]
    private Collection $produits;

    #[ORM\Column(type: "json", nullable: true)]
    private array $quantites = [];

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date = null;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
        $this->date = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTable(): ?TableRestaurant
    {
        return $this->table;
    }
    public function setTable(TableRestaurant $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function getProduits(): Collection
    {
        return $this->produits;
    }
    public function addProduit(Produit $produit, int $quantite = 1): self
    {
        if (!$this->produits->contains($produit)) $this->produits->add($produit);
        $this->quantites[$produit->getId()] = $quantite;
        return $this;
    }

    public function getQuantites(): array
    {
        return $this->quantites;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }
    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }
}
