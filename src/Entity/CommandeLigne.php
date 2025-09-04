<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CommandeLigne
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: "lignes")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $libelleProduit;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private string $prixHt;

    #[ORM\Column(type: "decimal", precision: 5, scale: 2)]
    private string $tauxTva;

    #[ORM\Column(type: "integer")]
    private int $quantite;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $categorieLibelle = null;

    #[ORM\Column(nullable: true)]
    private ?int $produitId = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCommande(): ?Commande
    {
        return $this->commande;
    }
    public function setCommande(Commande $commande): self
    {
        $this->commande = $commande;
        return $this;
    }
    public function getLibelleProduit(): string
    {
        return $this->libelleProduit;
    }
    public function setLibelleProduit(string $libelleProduit): self
    {
        $this->libelleProduit = $libelleProduit;
        return $this;
    }
    public function getPrixHt(): float
    {
        return (float)$this->prixHt;
    }
    public function setPrixHt(float $prixHt): self
    {
        $this->prixHt = $prixHt;
        return $this;
    }
    public function getTauxTva(): float
    {
        return (float)$this->tauxTva;
    }
    public function setTauxTva(float $tauxTva): self
    {
        $this->tauxTva = $tauxTva;
        return $this;
    }
    public function getQuantite(): int
    {
        return $this->quantite;
    }
    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }
    public function getCategorieLibelle(): ?string
    {
        return $this->categorieLibelle;
    }
    public function setCategorieLibelle(?string $categorieLibelle): self
    {
        $this->categorieLibelle = $categorieLibelle;
        return $this;
    }
    public function getProduitId(): ?int
    {
        return $this->produitId;
    }
    public function setProduitId(?int $produitId): self
    {
        $this->produitId = $produitId;
        return $this;
    }
}
