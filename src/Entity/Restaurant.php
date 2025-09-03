<?php
// src/Entity/Restaurant.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Restaurant
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $logo = null; // chemin image ou URL

    // getters & setters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getNom(): ?string
    {
        return $this->nom;
    }
    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }
    public function getAdresse(): ?string
    {
        return $this->adresse;
    }
    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }
    public function setTelephone(?string $tel): self
    {
        $this->telephone = $tel;
        return $this;
    }
    public function getLogo(): ?string
    {
        return $this->logo;
    }
    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }
}
