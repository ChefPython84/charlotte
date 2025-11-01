<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:User::class, inversedBy:"reservations")]
    #[ORM\JoinColumn(nullable:true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity:Salle::class, inversedBy:"reservations")]
    #[ORM\JoinColumn(nullable:false)]
    private ?Salle $salle = null;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateFin;

    #[ORM\OneToOne(targetEntity: DossierContrat::class, cascade: ['persist', 'remove'])]
    private ?DossierContrat $dossierContrat = null;
    
    // statut: option, dossier_en_attente, en_attente, confirme, annule
    #[ORM\Column(length:50)]
    private string $statut = 'en_attente';

    #[ORM\Column(type:"decimal", precision:10, scale:2, nullable:true)]
    private ?string $prixTotal = null;

    // Type de manifestation : 'L' (loisirs) ou 'T' (salon) ou autre
    #[ORM\Column(length:5, nullable:true)]
    private ?string $typeManifestation = null;

    // vacations chosen stored as JSON array, e.g. ["08:00-13:00","14:00-19:00"]
    #[ORM\Column(type:"json", nullable:true)]
    private ?array $vacations = null;

    // prestations/options (many-to-many to OptionService)
    #[ORM\ManyToMany(targetEntity:OptionService::class, inversedBy:"reservations")]
    private Collection $options;

    // Documents: store paths or small meta; flexible JSON
    #[ORM\Column(type:"json", nullable:true)]
    private ?array $documents = null; // keys: assurance, dossierTechnique, autorisations => path or null

    // dossier tracking
    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $dossierSubmittedAt = null;

    // contract lifecycle
    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $contractCreatedAt = null;

    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $contractSignedAt = null;

    #[ORM\OneToMany(mappedBy:"reservation", targetEntity:Facture::class, orphanRemoval:true)]
    private Collection $factures;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'reservation', orphanRemoval: true)]
    #[ORM\OrderBy(['dateEnvoi' => 'ASC'])] // Trie les commentaires du plus ancien au plus récent
    private Collection $commentaires;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->factures = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
    }

    // ---- getters / setters ----

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getSalle(): ?Salle { return $this->salle; }
    public function setSalle(?Salle $salle): self { $this->salle = $salle; return $this; }
    public function getDateDebut(): \DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }
    public function getDateFin(): \DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): self { $this->dateFin = $dateFin; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
    public function getPrixTotal(): ?float
    {
        return $this->prixTotal !== null ? (float) $this->prixTotal : null;
    }

    public function setPrixTotal(null|float|string $prixTotal): self
    {
        if ($prixTotal === null) {
            $this->prixTotal = null;
        } else {
            $this->prixTotal = number_format((float)$prixTotal, 2, '.', '');
        }
        return $this;
    }

    public function getTypeManifestation(): ?string { return $this->typeManifestation; }
    public function setTypeManifestation(?string $typeManifestation): self { $this->typeManifestation = $typeManifestation; return $this; }

    public function getVacations(): ?array { return $this->vacations; }
    public function setVacations(?array $vacations): self { $this->vacations = $vacations; return $this; }

    /** @return Collection<int, OptionService> */
    public function getOptions(): Collection { return $this->options; }
    public function addOption(OptionService $option): self {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
        }
        return $this;
    }
    public function removeOption(OptionService $option): self {
        $this->options->removeElement($option);
        return $this;
    }

    public function getDocuments(): ?array { return $this->documents; }
    public function setDocuments(?array $documents): self { $this->documents = $documents; return $this; }

    public function getDossierContrat(): ?DossierContrat
    {
        return $this->dossierContrat;
    }

    public function setDossierContrat(?DossierContrat $dossierContrat): self
    {
        $this->dossierContrat = $dossierContrat;
        return $this;
    }

    public function getDossierSubmittedAt(): ?\DateTimeInterface { return $this->dossierSubmittedAt; }
    public function setDossierSubmittedAt(?\DateTimeInterface $dossierSubmittedAt): self { $this->dossierSubmittedAt = $dossierSubmittedAt; return $this; }

    public function getContractCreatedAt(): ?\DateTimeInterface { return $this->contractCreatedAt; }
    public function setContractCreatedAt(?\DateTimeInterface $contractCreatedAt): self { $this->contractCreatedAt = $contractCreatedAt; return $this; }

    public function getContractSignedAt(): ?\DateTimeInterface { return $this->contractSignedAt; }
    public function setContractSignedAt(?\DateTimeInterface $contractSignedAt): self { $this->contractSignedAt = $contractSignedAt; return $this; }


    /** @return Collection<int, Commentaire> */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setReservation($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getReservation() === $this) {
                $commentaire->setReservation(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Facture> */
    public function getFactures(): Collection { return $this->factures; }
    public function addFacture(Facture $facture): self {
        if (!$this->factures->contains($facture)) {
            $this->factures->add($facture);
            $facture->setReservation($this);
        }
        return $this;
    }
    public function removeFacture(Facture $facture): self {
        if ($this->factures->removeElement($facture)) {
            if ($facture->getReservation() === $this) {
                $facture->setReservation(null);
            }
        }
        return $this;
    }

    // --- Helpers (business logic helpers) ---

    /**
     * Returns whether reservation has required documents (assurance + dossier technique)
     */
    public function hasRequiredDocuments(): bool
    {
        if (!is_array($this->documents)) {
            return false;
        }
        return !empty($this->documents['assurance'] ?? null) && !empty($this->documents['dossierTechnique'] ?? null);
    }
}
