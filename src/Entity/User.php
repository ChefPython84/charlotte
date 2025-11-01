<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // --- AJOUT DE CONSTANTES POUR LES RÔLES ---
    public const ROLE_CLIENT = 'ROLE_CLIENT';
    public const ROLE_GESTIONNAIRE = 'ROLE_GESTIONNAIRE';
    public const ROLE_MAIRIE = 'ROLE_MAIRIE'; // NOUVEAU
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    // ---

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(length:100)]
    private string $nom;

    #[ORM\Column(length:100)]
    private string $prenom;

    #[ORM\Column(length:180, unique:true)]
    private string $email;

    #[ORM\Column(length:20, nullable:true)]
    private ?string $telephone = null;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $motDePasse = null;

    // MODIFIÉ : Le rôle par défaut est 'ROLE_CLIENT' (plus logique que 'ROLE_USER')
    #[ORM\Column(length:50)]
    private string $role = self::ROLE_CLIENT; 

    // ... (vos autres propriétés : typeOrganisateur, siret, rna, etc. restent inchangées) ...
    #[ORM\Column(length:50, nullable:true)]
    private ?string $typeOrganisateur = null;

    #[ORM\Column(length:14, nullable:true)]
    private ?string $siret = null;

    #[ORM\Column(length:50, nullable:true)]
    private ?string $rna = null;

    #[ORM\Column(length:150, nullable:true)]
    private ?string $commune = null;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateInscription;

    #[ORM\OneToMany(mappedBy:"user", targetEntity:Reservation::class, orphanRemoval:false)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy:"user", targetEntity:Avis::class, orphanRemoval:true)]
    private Collection $avis;
    
    // ... (le constructeur reste inchangé) ...
    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->dateInscription = new \DateTime();
    }


    // ---- getters / setters ----
    // ... (tous vos getters/setters : getId, getNom, setNom, getEmail, etc. restent inchangés) ...
    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }
    public function getMotDePasse(): string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): self { $this->motDePasse = $motDePasse; return $this; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
    public function getTypeOrganisateur(): ?string { return $this->typeOrganisateur; }
    public function setTypeOrganisateur(?string $typeOrganisateur): self { $this->typeOrganisateur = $typeOrganisateur; return $this; }
    public function getSiret(): ?string { return $this->siret; }
    public function setSiret(?string $siret): self { $this->siret = $siret; return $this; }
    public function getRna(): ?string { return $this->rna; }
    public function setRna(?string $rna): self { $this->rna = $rna; return $this; }
    public function getCommune(): ?string { return $this->commune; }
    public function setCommune(?string $commune): self { $this->commune = $commune; return $this; }
    public function getDateInscription(): \DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $date): self { $this->dateInscription = $date; return $this; }
    public function getReservations(): Collection { return $this->reservations; }
    public function addReservation(Reservation $reservation): self { if (!$this->reservations->contains($reservation)) { $this->reservations->add($reservation); $reservation->setUser($this); } return $this; }
    public function removeReservation(Reservation $reservation): self { if ($this->reservations->removeElement($reservation)) { if ($reservation->getUser() === $this) { $reservation->setUser(null); } } return $this; }
    public function getAvis(): Collection { return $this->avis; }
    public function addAvis(Avis $avis): self { if (!$this->avis->contains($avis)) { $this->avis->add($avis); $avis->setUser($this); } return $this; }
    public function removeAvis(Avis $avis): self { if ($this->avis->removeElement($avis)) { if ($avis->getUser() === $this) { $avis->setUser(null); } } return $this; }


    // ---- Symfony UserInterface / PasswordAuthenticatedUserInterface ----
    public function getPassword(): string { return $this->motDePasse; }

    public function getUserIdentifier(): string { return $this->email; }

    /**
     * MODIFIÉ : Méthode getRoles() simplifiée et standardisée
     */
    public function getRoles(): array
    {
        // Récupère le rôle stocké (ex: 'ROLE_MAIRIE')
        $roles = [$this->role];

        // Garantit que chaque utilisateur connecté a au moins 'ROLE_USER'
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void { /* nothing */ }

    public function __toString(): string
    {
        return trim($this->nom . ' ' . $this->prenom);
    }
}