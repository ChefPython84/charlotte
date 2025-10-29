<?php

namespace App\Entity;

use App\Repository\DossierContratRepository; // Assurez-vous que le Repository existe
use Doctrine\DBAL\Types\Types; // Pour utiliser Types::TEXT et Types::BOOLEAN
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierContratRepository::class)]
class DossierContrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detailsManifestation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $besoinsTechniques = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $planSecuritePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assurancePath = null;

    // --- NOUVEAU CHAMP ---
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireMairie = null; // Champ pour les commentaires de la mairie

    // --- NOUVEAU CHAMP ---
    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    private bool $validationMairie = false; // Champ pour la case à cocher de validation


    // --- NOUVEAUX CHAMPS PRESTATAIRE ---
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentairePrestataire = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    private bool $validationPrestataire = false;
    // --- FIN ---
    
    // --- RELATION INVERSE (si nécessaire, mais pas obligatoire ici) ---
    // #[ORM\OneToOne(mappedBy: 'dossierContrat', targetEntity: Reservation::class)]
    // private ?Reservation $reservation = null; 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDetailsManifestation(): ?string
    {
        return $this->detailsManifestation;
    }

    public function setDetailsManifestation(?string $detailsManifestation): static
    {
        $this->detailsManifestation = $detailsManifestation;
        return $this;
    }

    public function getBesoinsTechniques(): ?string
    {
        return $this->besoinsTechniques;
    }

    public function setBesoinsTechniques(?string $besoinsTechniques): static
    {
        $this->besoinsTechniques = $besoinsTechniques;
        return $this;
    }

    public function getPlanSecuritePath(): ?string
    {
        return $this->planSecuritePath;
    }

    public function setPlanSecuritePath(?string $planSecuritePath): static
    {
        $this->planSecuritePath = $planSecuritePath;
        return $this;
    }

    public function getAssurancePath(): ?string
    {
        return $this->assurancePath;
    }

    public function setAssurancePath(?string $assurancePath): static
    {
        $this->assurancePath = $assurancePath;
        return $this;
    }

    // --- GETTERS/SETTERS POUR NOUVEAUX CHAMPS ---

    public function getCommentaireMairie(): ?string
    {
        return $this->commentaireMairie;
    }

    public function setCommentaireMairie(?string $commentaireMairie): static
    {
        $this->commentaireMairie = $commentaireMairie;
        return $this;
    }

    public function isValidationMairie(): bool
    {
        return $this->validationMairie;
    }

    public function setValidationMairie(bool $validationMairie): static
    {
        $this->validationMairie = $validationMairie;
        return $this;
    }

    public function getCommentairePrestataire(): ?string
    {
        return $this->commentairePrestataire;
    }

    public function setCommentairePrestataire(?string $commentairePrestataire): static
    {
        $this->commentairePrestataire = $commentairePrestataire;
        return $this;
    }

    public function isValidationPrestataire(): bool
    {
        return $this->validationPrestataire;
    }

    public function setValidationPrestataire(bool $validationPrestataire): static
    {
        $this->validationPrestataire = $validationPrestataire;
        return $this;
    }

    // --- GETTER/SETTER POUR RELATION INVERSE (si ajoutée) ---
    // public function getReservation(): ?Reservation
    // {
    //     return $this->reservation;
    // }

    // public function setReservation(?Reservation $reservation): static
    // {
    //     // set the owning side of the relation if necessary
    //     if ($reservation !== null && $reservation->getDossierContrat() !== $this) {
    //         $reservation->setDossierContrat($this);
    //     }
    //     $this->reservation = $reservation;
    //     return $this;
    // }
}