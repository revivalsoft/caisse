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

    #[ORM\OneToMany(mappedBy: "commande", targetEntity: CommandeLigne::class, cascade: ["persist", "remove"])]
    private Collection $lignes;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date = null;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->date = new \DateTimeImmutable();
    }

    public function addLigne(CommandeLigne $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }
        return $this;
    }

    public function getLignes(): Collection
    {
        return $this->lignes;
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
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }
    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    // Méthodes pratiques pour reporting
    public function getTotalHt(): float
    {
        return array_sum(array_map(fn($l) => $l->getPrixHt() * $l->getQuantite(), $this->lignes->toArray()));
    }

    public function getTotalTva(): float
    {
        return array_sum(array_map(fn($l) => ($l->getPrixHt() * $l->getQuantite()) * ($l->getTauxTva() / 100), $this->lignes->toArray()));
    }

    public function getTotalTtc(): float
    {
        return $this->getTotalHt() + $this->getTotalTva();
    }
}
