<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Entity\Paiement; // <-- Votre entité Paiement
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/paiement')]
class PaiementController extends AbstractController
{
    private EntityManagerInterface $em;
    private string $stripeSecretKey;

    public function __construct(EntityManagerInterface $em, string $stripeSecretKey)
    {
        $this->em = $em;
        $this->stripeSecretKey = $stripeSecretKey;
    }

    /**
     * Étape 1: L'utilisateur clique sur "Payer"
     * On crée une session Stripe et on le redirige.
     */
    #[Route('/facture/{id}/checkout', name: 'paiement_checkout')]
    public function checkout(Facture $facture): Response
    {
        // Sécurité : Vérifier que la facture appartient bien au client connecté
        if ($facture->getReservation()->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès non autorisé à cette facture.');
            return $this->redirectToRoute('app_compte');
        }

        // Sécurité : Vérifier que la facture est bien "en attente"
        if ($facture->getStatut() !== 'en attente') {
            $this->addFlash('warning', 'Cette facture n\'est pas en attente de paiement.');
            return $this->redirectToRoute('facture_show', ['id' => $facture->getId()]);
        }

        Stripe::setApiKey($this->stripeSecretKey);

        // On crée la session de paiement
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Facture #' . $facture->getId() . ' - Réservation ' . $facture->getReservation()->getSalle()->getNom(),
                    ],
                    // Stripe travaille en centimes !
                    'unit_amount' => $facture->getMontant() * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            // On stocke l'ID de la facture pour la retrouver au retour
            'metadata' => [
                'facture_id' => $facture->getId()
            ],
            // URLs de succès et d'annulation
            'success_url' => $this->generateUrl('paiement_succes', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('paiement_annule', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // On redirige l'utilisateur vers la page de paiement Stripe
        return $this->redirect($session->url, 303);
    }

    /**
     * Étape 2: L'utilisateur a payé
     * Stripe le redirige ici.
     */
    #[Route('/succes', name: 'paiement_succes')]
    public function succes(Request $request, FactureRepository $factureRepo): Response
    {
        Stripe::setApiKey($this->stripeSecretKey);
        
        $sessionId = $request->query->get('session_id');

        try {
            // On récupère la session Stripe pour vérifier le paiement
            $session = Session::retrieve($sessionId);

            // On récupère l'ID de la facture que nous avions stocké
            $factureId = $session->metadata->facture_id;

            /** @var Facture $facture */
            $facture = $factureRepo->find($factureId);

            if (!$facture) {
                $this->addFlash('danger', 'Erreur lors de la récupération de la facture.');
                return $this->redirectToRoute('app_compte');
            }

            // --- MISE À JOUR DE LA FACTURE ---
            if ($facture->getStatut() === 'en attente') {
                
                // 1. Mettre à jour la facture
                $facture->setStatut('payée');

                // 2. Créer une entité Paiement pour l'historique
                $paiement = new Paiement();
                $paiement->setFacture($facture);
                $paiement->setMontant($facture->getMontant()); // On reprend le montant de la facture
                $paiement->setDatePaiement(new \DateTime());
                $paiement->setMethode('Stripe');
                $paiement->setStatut('réussi');
                // $paiement->setTransactionId($session->payment_intent); // Si vous avez un champ pour l'ID de transaction

                $this->em->persist($paiement);
                $this->em->flush();
                
                $this->addFlash('success', 'Paiement effectué avec succès ! Votre facture est réglée.');
            }

            // Rediriger vers la page de la facture (qui affichera "Payée")
            return $this->redirectToRoute('facture_show', ['id' => $facture->getId()]);

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la vérification du paiement : ' . $e->getMessage());
            return $this->redirectToRoute('app_compte');
        }
    }

    /**
     * Étape 3: L'utilisateur a annulé
     * Stripe le redirige ici.
     */
    #[Route('/annule/{id}', name: 'paiement_annule')]
    public function annule(Facture $facture): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé. Votre facture est toujours en attente.');
        return $this->redirectToRoute('facture_show', ['id' => $facture->getId()]);
    }
}