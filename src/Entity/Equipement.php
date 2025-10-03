<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Equipement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(length:100)]
    private string $nom;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity:Salle::class, mappedBy:"equipements")]
    private Collection $salles;

    public function __construct()
    {
        $this->salles = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    /** @return Collection<int, Salle> */
    public function getSalles(): Collection { return $this->salles; }

    public function addSalle(Salle $salle): self {
        if (!$this->salles->contains($salle)) {
            $this->salles->add($salle);
            $salle->addEquipement($this);
        }
        return $this;
    }
    public function removeSalle(Salle $salle): self {
        if ($this->salles->removeElement($salle)) {
            $salle->removeEquipement($this);
        }
        return $this;
    }
}
