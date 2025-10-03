<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Salle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(length:150)]
    private string $nom;

    #[ORM\Column(type:"text")]
    private string $description;

    #[ORM\Column(type:"integer")]
    private int $capacite;

     #[ORM\Column(type:"decimal", precision:10, scale:2)]
    private string $prixJour;

    #[ORM\Column(type:"decimal", precision:10, scale:2)]
    private string $prixHeure;

    #[ORM\Column(length:255)]
    private string $adresse;

    #[ORM\Column(length:100)]
    private string $ville;

    #[ORM\Column(length:10)]
    private string $codePostal;

    #[ORM\Column(length:50)]
    private string $statut = 'disponible';

    // store default vacations available for the room (json array of time ranges)
    #[ORM\Column(type:"json", nullable:true)]
    private ?array $vacations = ["08:00-13:00","14:00-19:00","20:00-00:00","00:00-03:00"];

    #[ORM\OneToMany(mappedBy:"salle", targetEntity:Reservation::class, orphanRemoval:true)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy:"salle", targetEntity:Disponibilite::class, orphanRemoval:true)]
    private Collection $disponibilites;

    #[ORM\OneToMany(mappedBy:"salle", targetEntity:PhotoSalle::class, orphanRemoval:true)]
    private Collection $photos;

    #[ORM\OneToMany(mappedBy:"salle", targetEntity:Avis::class, orphanRemoval:true)]
    private Collection $avis;

    #[ORM\ManyToMany(targetEntity:Equipement::class, inversedBy:"salles")]
    private Collection $equipements;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->disponibilites = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->equipements = new ArrayCollection();
    }

    // ---- getters / setters ----

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getCapacite(): int { return $this->capacite; }
    public function setCapacite(int $capacite): self { $this->capacite = $capacite; return $this; }
    
    public function getPrixJour(): float
    {
        return (float) $this->prixJour;
    }

    public function setPrixJour(float|string $prixJour): self
    {
        $this->prixJour = number_format((float)$prixJour, 2, '.', '');
        return $this;
    }

    public function getPrixHeure(): float
    {
        return (float) $this->prixHeure;
    }

    public function setPrixHeure(float|string $prixHeure): self
    {
        $this->prixHeure = number_format((float)$prixHeure, 2, '.', '');
        return $this;
    }
    public function getAdresse(): string { return $this->adresse; }
    public function setAdresse(string $adresse): self { $this->adresse = $adresse; return $this; }
    public function getVille(): string { return $this->ville; }
    public function setVille(string $ville): self { $this->ville = $ville; return $this; }
    public function getCodePostal(): string { return $this->codePostal; }
    public function setCodePostal(string $codePostal): self { $this->codePostal = $codePostal; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    public function getVacations(): ?array { return $this->vacations; }
    public function setVacations(?array $vacations): self { $this->vacations = $vacations; return $this; }

    /** @return Collection<int, Reservation> */
    public function getReservations(): Collection { return $this->reservations; }
    public function addReservation(Reservation $reservation): self {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setSalle($this);
        }
        return $this;
    }
    public function removeReservation(Reservation $reservation): self {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getSalle() === $this) {
                $reservation->setSalle(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Disponibilite> */
    public function getDisponibilites(): Collection { return $this->disponibilites; }
    public function addDisponibilite(Disponibilite $disponibilite): self {
        if (!$this->disponibilites->contains($disponibilite)) {
            $this->disponibilites->add($disponibilite);
            $disponibilite->setSalle($this);
        }
        return $this;
    }
    public function removeDisponibilite(Disponibilite $disponibilite): self {
        if ($this->disponibilites->removeElement($disponibilite)) {
            if ($disponibilite->getSalle() === $this) {
                $disponibilite->setSalle(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, PhotoSalle> */
    public function getPhotos(): Collection { return $this->photos; }
    public function addPhoto(PhotoSalle $photo): self {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setSalle($this);
        }
        return $this;
    }
    public function removePhoto(PhotoSalle $photo): self {
        if ($this->photos->removeElement($photo)) {
            if ($photo->getSalle() === $this) {
                $photo->setSalle(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Avis> */
    public function getAvis(): Collection { return $this->avis; }
    public function addAvis(Avis $avis): self {
        if (!$this->avis->contains($avis)) {
            $this->avis->add($avis);
            $avis->setSalle($this);
        }
        return $this;
    }
    public function removeAvis(Avis $avis): self {
        if ($this->avis->removeElement($avis)) {
            if ($avis->getSalle() === $this) {
                $avis->setSalle(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Equipement> */
    public function getEquipements(): Collection { return $this->equipements; }
    public function addEquipement(Equipement $equipement): self {
        if (!$this->equipements->contains($equipement)) {
            $this->equipements->add($equipement);
            $equipement->addSalle($this);
        }
        return $this;
    }
    public function removeEquipement(Equipement $equipement): self {
        if ($this->equipements->removeElement($equipement)) {
            // also remove backref
            $equipement->removeSalle($this);
        }
        return $this;
    }

    public function __toString(): string { return $this->nom ?? 'Salle'; }
}
