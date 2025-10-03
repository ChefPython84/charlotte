<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:Salle::class, inversedBy:"disponibilites")]
    #[ORM\JoinColumn(nullable:false)]
    private ?Salle $salle = null;

    // dateDebut/dateFin are full datetimes (useful if disponibilités cross day boundaries)
    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateFin;

    // For convenience keep separate times as well (optional)
    #[ORM\Column(type:"time", nullable:true)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type:"time", nullable:true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(length:50)]
    private string $statut = 'libre';

    // --- getters / setters ---
    public function getId(): ?int { return $this->id; }
    public function getSalle(): ?Salle { return $this->salle; }
    public function setSalle(?Salle $salle): self { $this->salle = $salle; return $this; }
    public function getDateDebut(): \DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }
    public function getDateFin(): \DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): self { $this->dateFin = $dateFin; return $this; }
    public function getHeureDebut(): ?\DateTimeInterface { return $this->heureDebut; }
    public function setHeureDebut(?\DateTimeInterface $heureDebut): self { $this->heureDebut = $heureDebut; return $this; }
    public function getHeureFin(): ?\DateTimeInterface { return $this->heureFin; }
    public function setHeureFin(?\DateTimeInterface $heureFin): self { $this->heureFin = $heureFin; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
}
