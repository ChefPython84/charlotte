<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Repository\FactureRepository;
use App\Service\PaiementService; // <-- AJOUTER
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
    // On n'a plus besoin de l'EntityManager ici, le Service s'en charge
    private string $stripeSecretKey;

    public function __construct(string $stripeSecretKey)
    {
        $this->stripeSecretKey = $stripeSecretKey;
    }

    /**
     * Étape 1: L'utilisateur clique sur "Payer"
     * (Cette méthode ne change pas)
     */
    #[Route('/facture/{id}/checkout', name: 'paiement_checkout')]
    public function checkout(Facture $facture): Response
    {
        // (Toute votre logique de sécurité et de création de session reste identique)
        if ($facture->getReservation()->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès non autorisé à cette facture.');
            return $this->redirectToRoute('app_compte');
        }
        if ($facture->getStatut() !== 'en attente') {
            $this->addFlash('warning', 'Cette facture n\'est pas en attente de paiement.');
            return $this->redirectToRoute('facture_show', ['id' => $facture->getId()]);
        }
        Stripe::setApiKey($this->stripeSecretKey);
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [ 'name' => 'Facture #' . $facture->getId() . ' - Réservation ' . $facture->getReservation()->getSalle()->getNom() ],
                    'unit_amount' => $facture->getMontant() * 100,
                ], 'quantity' => 1,
            ]],
            'mode' => 'payment',
            'metadata' => [ 'facture_id' => $facture->getId() ],
            'success_url' => $this->generateUrl('paiement_succes', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('paiement_annule', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        return $this->redirect($session->url, 303);
    }

    /**
     * Étape 2: L'utilisateur a payé
     * Stripe le redirige ici. (MODIFIÉ)
     */
    #[Route('/succes', name: 'paiement_succes')]
    public function succes(
        Request $request, 
        FactureRepository $factureRepo,
        PaiementService $paiementService // <-- INJECTER LE SERVICE
    ): Response {
        
        Stripe::setApiKey($this->stripeSecretKey);
        $sessionId = $request->query->get('session_id');

        try {
            $session = Session::retrieve($sessionId);
            $factureId = $session->metadata->facture_id;
            $facture = $factureRepo->find($factureId);

            if (!$facture) {
                $this->addFlash('danger', 'Erreur lors de la récupération de la facture.');
                return $this->redirectToRoute('app_compte');
            }

            // --- MISE À JOUR : On utilise le service ---
            $paymentProcessed = $paiementService->confirmPayment($facture, $session->id);
            // --- FIN MISE À JOUR ---
            
            if ($paymentProcessed) {
                $this->addFlash('success', 'Paiement effectué avec succès ! Votre facture est réglée.');
            } else {
                // Si 'false', c'est que le webhook l'a déjà traitée
                $this->addFlash('info', 'Votre paiement a bien été reçu.');
            }

            return $this->redirectToRoute('facture_show', ['id' => $facture->getId()]);

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la vérification du paiement : ' . $e->getMessage());
            return $this->redirectToRoute('app_compte');
        }
    }

    /**
     * Étape 3: L'utilisateur a annulé
     * (Cette méthode ne change pas)
     */
    #[Route('/annule/{id}', name: 'paiement_annule')]
    public function annule(Facture $facture): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé. Votre facture est toujours en attente.');
        return $this->redirectToRoute('facture_show', ['id' => $facture->getId()]);
    }
}