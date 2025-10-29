<?php

// src/MessageHandler/SendContractNotificationHandler.php
namespace App\MessageHandler;

use App\Message\SendContractNotification;
use Psr\Log\LoggerInterface; // Optionnel : pour logger les erreurs
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

/**
 * Ce Handler est responsable de l'envoi des emails de notification de contrat.
 * Il est automatiquement appelé par Symfony Messenger lorsqu'un message
 * de type SendContractNotification est dispatché dans le bus.
 */
#[AsMessageHandler] // Indique à Symfony que cette classe est un Handler
final class SendContractNotificationHandler
{
    private MailerInterface $mailer;
    private ?LoggerInterface $logger; // Optionnel

    // Injecte le service Mailer de Symfony
    public function __construct(MailerInterface $mailer, ?LoggerInterface $logger = null)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * La méthode magique __invoke est appelée par Messenger.
     * Elle prend en argument le message à traiter.
     */
    public function __invoke(SendContractNotification $message): void
    {
        // Crée l'objet Email à partir des informations du message
        $email = (new Email())
            // **IMPORTANT** : Configurez cette adresse dans config/packages/mailer.yaml ou .env
            ->from('noreply@votresite.com') 
            ->to($message->getRecipientEmail())
            ->subject($message->getSubject())
            // Pour l'instant, on envoie en texte brut
            ->text($message->getBody()); 
            // ->html('<p>Vous pourriez utiliser un template Twig ici...</p>'); // Optionnel: pour des emails HTML

        try {
            // Essaie d'envoyer l'email
            $this->mailer->send($email);

            // Optionnel : Loggez le succès
            $this->logger?->info(sprintf('Email de notification envoyé à %s pour le sujet "%s"', $message->getRecipientEmail(), $message->getSubject()));

        } catch (TransportExceptionInterface $e) {
            // En cas d'erreur lors de l'envoi (ex: serveur SMTP inaccessible)
            // Optionnel : Loggez l'erreur
            $this->logger?->error(sprintf('Erreur lors de l\'envoi de l\'email à %s : %s', $message->getRecipientEmail(), $e->getMessage()));

            // Optionnel : Vous pouvez relancer l'exception si vous voulez que Messenger
            // réessaie d'envoyer le message plus tard (selon votre config de retries)
            // throw $e; 
        } catch (\Exception $e) {
             // Autres erreurs potentielles
             $this->logger?->error(sprintf('Erreur inattendue lors de l\'envoi de l\'email à %s : %s', $message->getRecipientEmail(), $e->getMessage()));
             // throw $e; 
        }
    }
}