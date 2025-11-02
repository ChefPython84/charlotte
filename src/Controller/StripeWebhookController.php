<?php

namespace App\Controller;

use App\Repository\FactureRepository;
use App\Service\PaiementService;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    // --- DÉBUT MODIFICATION : On déplace tout dans le constructeur ---
    private string $stripeSecretKey;
    private string $stripeWebhookSecret;
    private PaiementService $paiementService;
    private FactureRepository $factureRepo;

    public function __construct(
        string $stripeSecretKey, // Injecté depuis services.yaml
        string $stripeWebhookSecret, // Injecté depuis services.yaml
        PaiementService $paiementService,
        FactureRepository $factureRepo
    ) {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->stripeWebhookSecret = $stripeWebhookSecret;
        $this->paiementService = $paiementService;
        $this->factureRepo = $factureRepo;
    }
    // --- FIN MODIFICATION ---

    /**
     * C'est l'URL que vous donnerez à Stripe
     */
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        // 1. Récupérer la signature et le payload
        $signature = $request->headers->get('Stripe-Signature');
        $payload = $request->getContent();

        // 2. Vérifier la signature (sécurité)
        try {
            // --- CORRECTION : On utilise la clé injectée ---
            Stripe::setApiKey($this->stripeSecretKey); 
            // --- FIN CORRECTION ---

            $event = Webhook::constructEvent(
                $payload, $signature, $this->stripeWebhookSecret // On utilise la propriété
            );
        } catch (\UnexpectedValueException $e) {
            // Signature invalide (payload)
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Signature invalide (signature)
            return new Response('Invalid signature', 400);
        }

        // 3. Traiter l'événement
        if ($event->type == 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;

            // 4. Récupérer la facture depuis les métadonnées
            $factureId = $session->metadata->facture_id ?? null;
            if (!$factureId) {
                return new Response('Missing Facture ID in metadata', 400);
            }
            
            $facture = $this->factureRepo->find($factureId); // On utilise la propriété
            if (!$facture) {
                return new Response('Facture not found', 404);
            }

            // 5. Appeler notre service pour finaliser le paiement
            $this->paiementService->confirmPayment($facture, $session->id); // On utilise la propriété
        }

        // 6. Répondre à Stripe pour accuser réception
        return new Response('Webhook handled', 200);
    }
}