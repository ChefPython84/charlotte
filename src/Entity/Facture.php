<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Facture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:Reservation::class, inversedBy:"factures")]
    #[ORM\JoinColumn(nullable:false)]
    private ?Reservation $reservation = null;

    #[ORM\Column(type:"decimal", precision:10, scale:2)]
    private string $montant;

    #[ORM\Column(length:50)]
    private string $statut;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateFacture;

    #[ORM\OneToMany(mappedBy:"facture", targetEntity:Paiement::class, orphanRemoval:true)]
    private Collection $paiements;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->dateFacture = new \DateTime();
    }

    // getters / setters
    public function getId(): ?int { return $this->id; }
    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $reservation): self { $this->reservation = $reservation; return $this; }
     public function getMontant(): float
    {
        return (float) $this->montant;
    }

    /**
     * Accept float or string; store as decimal string with 2 decimals.
     */
    public function setMontant(float|string $montant): self
    {
        $this->montant = number_format((float)$montant, 2, '.', '');
        return $this;
    }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
    public function getDateFacture(): \DateTimeInterface { return $this->dateFacture; }
    public function setDateFacture(\DateTimeInterface $dateFacture): self { $this->dateFacture = $dateFacture; return $this; }

    /** @return Collection<int,Paiement> */
    public function getPaiements(): Collection { return $this->paiements; }
    public function addPaiement(Paiement $paiement): self {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setFacture($this);
        }
        return $this;
    }
    public function removePaiement(Paiement $paiement): self {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getFacture() === $this) {
                $paiement->setFacture(null);
            }
        }
        return $this;
    }
}
