<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OptionService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(length:100)]
    private string $nom;

    #[ORM\Column(type:"decimal", precision:10, scale:2)]
    private string $prix;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity:Reservation::class, mappedBy:"options")]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getPrix(): float
    {
        return (float) $this->prix;
    }

    public function setPrix(float|string $prix): self
    {
        $this->prix = number_format((float)$prix, 2, '.', '');
        return $this;
    }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    /** @return Collection<int, Reservation> */
    public function getReservations(): Collection { return $this->reservations; }
    public function addReservation(Reservation $reservation): self {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->addOption($this);
        }
        return $this;
    }
    public function removeReservation(Reservation $reservation): self {
        if ($this->reservations->removeElement($reservation)) {
            // no-op on reservation side (it will remove relation via DB)
        }
        return $this;
    }
}
