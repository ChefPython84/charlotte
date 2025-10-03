<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:Facture::class, inversedBy:"paiements")]
    #[ORM\JoinColumn(nullable:false)]
    private ?Facture $facture = null;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $datePaiement;

    #[ORM\Column(type:"decimal", precision:10, scale:2)]
    private string $montant;

    #[ORM\Column(length:50)]
    private string $methode; // CB, virement, PayPal, etc.

    #[ORM\Column(length:50)]
    private string $statut; // réussi, échoué, en attente

    public function __construct()
    {
        $this->datePaiement = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getFacture(): ?Facture { return $this->facture; }
    public function setFacture(?Facture $facture): self { $this->facture = $facture; return $this; }
    public function getDatePaiement(): \DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(\DateTimeInterface $date): self { $this->datePaiement = $date; return $this; }
    public function getMontant(): float
    {
        return (float) $this->montant;
    }

    public function setMontant(float|string $montant): self
    {
        $this->montant = number_format((float)$montant, 2, '.', '');
        return $this;
    }
    public function getMethode(): string { return $this->methode; }
    public function setMethode(string $methode): self { $this->methode = $methode; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
}
