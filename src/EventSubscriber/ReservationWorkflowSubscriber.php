<?php

namespace App\EventSubscriber;

use App\Entity\Reservation;
use App\Message\SendContractNotification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; // AJOUT
use Symfony\Component\Workflow\Event\TransitionEvent;
// ... (use UserRepository si besoin) ...

class ReservationWorkflowSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $bus;
    private UrlGeneratorInterface $urlGenerator; // AJOUT

    public function __construct(
        MessageBusInterface $bus, 
        UrlGeneratorInterface $urlGenerator // AJOUT
        /*, UserRepository $userRepo */
    ) {
        $this->bus = $bus;
        $this->urlGenerator = $urlGenerator; // AJOUT
        // $this->userRepo = $userRepo;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.reservation_contract.transition' => 'onTransition',
        ];
    }

    public function onTransition(TransitionEvent $event): void
    {
        /** @var Reservation $reservation */
        $reservation = $event->getSubject();
        $transitionName = $event->getTransition()->getName();

        // Génère l'URL absolue vers le tunnel de contrat
        $contractUrl = $this->urlGenerator->generate('contrat_tunnel', [
            'id' => $reservation->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $recipientEmail = null;
        $subject = null;
        $messageBody = null;

        // --- Définir qui reçoit quel email pour quelle transition ---

        // Exemple 1: Le client a soumis son dossier -> Notifier l'admin/loueur
        if ($transitionName === 'client_soumet_dossier') {
            $recipientEmail = 'admin@votresite.com'; // **À REMPLACER**
            $subject = "Dossier soumis pour réservation #" . $reservation->getId();
            $messageBody = sprintf(
                "Le client %s a soumis son dossier pour la réservation #%d (%s).\nVous pouvez le valider ici : %s",
                $reservation->getUser()?->getNom(),
                $reservation->getId(),
                $reservation->getSalle()?->getNom(),
                $contractUrl // Utilise l'URL générée
            );
        }

        // Exemple 2: L'admin (loueur) a validé le dossier -> Notifier la Mairie
        elseif ($transitionName === 'loueur_valide_dossier') {
            $recipientEmail = 'mairie@votresite.com'; // **À REMPLACER**
            $subject = "Validation Mairie requise pour réservation #" . $reservation->getId();
            $messageBody = sprintf(
                "Le dossier de la réservation #%d (%s) attend votre validation (Mairie).\nConsulter le dossier : %s",
                $reservation->getId(),
                $reservation->getSalle()?->getNom(),
                $contractUrl
            );
        }
        
        // Exemple 3: La Mairie a validé -> Notifier le Prestataire
        elseif ($transitionName === 'mairie_valide_dossier') {
            $recipientEmail = 'prestataire@votresite.com'; // **À REMPLACER**
            $subject = "Validation Prestataire requise pour réservation #" . $reservation->getId();
             $messageBody = sprintf(
                "Le dossier de la réservation #%d (%s) attend votre validation (Prestataire).\nConsulter le dossier : %s",
                $reservation->getId(),
                $reservation->getSalle()?->getNom(),
                $contractUrl
            );
        }
        
        // Exemple 4: Tout est validé -> Notifier le Client
        elseif ($transitionName === 'prestataire_valide_dossier') { 
             $recipientEmail = $reservation->getUser()?->getEmail(); 
             $subject = "Votre contrat pour la réservation #" . $reservation->getId() . " est prêt";
             $messageBody = sprintf(
                "Bonjour %s,\nVotre dossier pour la réservation #%d (%s) a été validé par toutes les parties.\nVotre contrat est prêt à être signé.\nAccéder à votre espace contrat : %s",
                 $reservation->getUser()?->getPrenom(),
                $reservation->getId(),
                $reservation->getSalle()?->getNom(),
                $contractUrl
            );
        }
        
        // --- AJOUT : Le client a signé -> Notifier le client ET l'admin ---
        elseif ($transitionName === 'client_signe_contrat') {
            // 1. Envoyer au Client
            $clientEmail = $reservation->getUser()?->getEmail();
            if ($clientEmail) {
                $this->bus->dispatch(new SendContractNotification(
                    $clientEmail,
                    "Confirmation de signature - Réservation #" . $reservation->getId(),
                    sprintf(
                        "Bonjour %s,\nNous vous confirmons la signature électronique de votre contrat pour la réservation #%d (%s).\nVotre réservation est maintenant confirmée.\nConsulter le contrat signé : %s",
                        $reservation->getUser()?->getPrenom(),
                        $reservation->getId(),
                        $reservation->getSalle()?->getNom(),
                        $contractUrl
                    )
                ));
            }
            
            // 2. Envoyer à l'Admin
            $adminEmail = 'admin@votresite.com'; // **À REMPLACER**
            $this->bus->dispatch(new SendContractNotification(
                $adminEmail,
                "Contrat SIGNÉ - Réservation #" . $reservation->getId(),
                sprintf(
                    "Le client %s a signé le contrat pour la réservation #%d (%s).\nLa réservation est confirmée.\nConsulter le dossier : %s",
                    $reservation->getUser()?->getNom(),
                    $reservation->getId(),
                    $reservation->getSalle()?->getNom(),
                    $contractUrl
                )
            ));

            // On met 'return' car on a géré l'envoi manuellement
            return; 
        }
        // ... (autres transitions comme 'annuler') ...


        // --- Envoi du message à Messenger (pour les cas 1-4) ---
        if ($recipientEmail && $subject && $messageBody) {
            $emailMessage = new SendContractNotification($recipientEmail, $subject, $messageBody);
            $this->bus->dispatch($emailMessage);
        }
    }
}