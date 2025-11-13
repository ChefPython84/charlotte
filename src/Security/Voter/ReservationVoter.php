<?php
// src/Security/Voter/ReservationVoter.php

namespace App\Security\Voter;

use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security; // <-- Important
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ReservationVoter extends Voter
{
    // Les noms des transitions de votre workflow
    // (config/packages/workflow.yaml)
    public const ADMIN_DEMANDE_DOSSIER = 'admin_demande_dossier';
    public const CLIENT_SOUMET_DOSSIER = 'client_soumet_dossier';
    public const LOUEUR_VALIDE_DOSSIER = 'loueur_valide_dossier';
    public const MAIRIE_VALIDE_DOSSIER = 'mairie_valide_dossier';
    public const PRESTATAIRE_VALIDE_DOSSIER = 'prestataire_valide_dossier';
    public const CLIENT_SIGNE_CONTRAT = 'client_signe_contrat';
    public const ANNULER = 'annuler';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * C'est la méthode qui manquait.
     * Elle dit à Symfony : "Ce Voter ne s'occupe que des attributs
     * listés ci-dessous et seulement si l'objet est une instance de Reservation."
     */
    protected function supports(string $attribute, $subject): bool
    {
        // Si l'attribut (la permission) n'est pas l'un des nôtres, on ignore
        if (!in_array($attribute, [
            self::ADMIN_DEMANDE_DOSSIER,
            self::CLIENT_SOUMET_DOSSIER,
            self::LOUEUR_VALIDE_DOSSIER,
            self::MAIRIE_VALIDE_DOSSIER,
            self::PRESTATAIRE_VALIDE_DOSSIER,
            self::CLIENT_SIGNE_CONTRAT,
            self::ANNULER,
        ])) {
            return false;
        }

        // Si l'objet n'est pas une réservation, on ignore
        if (!$subject instanceof Reservation) {
            return false;
        }

        return true;
    }

    /**
     * C'est la méthode contenant la logique de permission.
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        // Si l'utilisateur n'est pas connecté, on refuse l'accès
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Reservation $reservation */
        $reservation = $subject; // On sait que c'est une Reservation grâce à supports()

        // L'ADMIN peut tout faire (grâce à l'héritage des rôles)
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Vérification de la logique métier
        switch ($attribute) {
            case self::CLIENT_SOUMET_DOSSIER:
            case self::CLIENT_SIGNE_CONTRAT:
                // Seul le client propriétaire de la réservation peut le faire
                // (On caste $user car on sait que c'est notre App\Entity\User)
                /** @var User $user */
                return $reservation->getUser() === $user;

            case self::ADMIN_DEMANDE_DOSSIER:
            case self::LOUEUR_VALIDE_DOSSIER:
            case self::ANNULER:
                // Seul le gestionnaire (loueur) peut le faire
                return $this->security->isGranted('ROLE_GESTIONNAIRE');

            case self::MAIRIE_VALIDE_DOSSIER:
                // Seule la mairie peut le faire
                return $this->security->isGranted('ROLE_MAIRIE');

            case self::PRESTATAIRE_VALIDE_DOSSIER:
                // Mettez ici le rôle du prestataire s'il existe,
                // sinon on le laisse au gestionnaire/admin
                // Ex: return $this->security->isGranted('ROLE_PRESTATAIRE');
                return $this->security->isGranted('ROLE_GESTIONNAIRE');
        }

        return false;
    }
}