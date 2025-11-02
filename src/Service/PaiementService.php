<?php

namespace App\Service;

use App\Entity\Facture;
use App\Entity\Paiement;
use Doctrine\ORM\EntityManagerInterface;

class PaiementService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Logique centrale pour confirmer un paiement.
     * Cette fonction est "idempotente" : elle ne s'exécute qu'une fois.
     */
    public function confirmPayment(Facture $facture, string $sessionId = null): bool
    {
        // 1. Vérifie si la facture est bien "en attente"
        // Si elle est déjà "payée", on ne fait rien.
        if ($facture->getStatut() === 'en attente') {
            
            // 2. Mettre à jour la facture
            $facture->setStatut('payée');

            // 3. Créer une entité Paiement pour l'historique
            $paiement = new Paiement();
            $paiement->setFacture($facture);
            $paiement->setMontant($facture->getMontant());
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setMethode('Stripe'); // ou 'CB'
            $paiement->setStatut('réussi');
            
            // Optionnel: stocker l'ID de session Stripe si vous avez un champ pour
            // $paiement->setTransactionId($sessionId); 

            $this->em->persist($paiement);
            $this->em->flush();

            return true; // Le paiement a été traité
        }

        return false; // Le paiement était déjà traité
    }
}