<?php

// src/Message/SendContractNotification.php
namespace App\Message;

/**
 * Représente la demande d'envoi d'un email de notification de contrat.
 * C'est un simple objet de données (DTO - Data Transfer Object).
 */
final class SendContractNotification
{
    private string $recipientEmail;
    private string $subject;
    private string $body;

    /**
     * Constructeur.
     *
     * @param string $recipientEmail L'adresse email du destinataire.
     * @param string $subject        L'objet de l'email.
     * @param string $body           Le corps de l'email (texte brut ici, mais pourrait être HTML).
     */
    public function __construct(string $recipientEmail, string $subject, string $body)
    {
        $this->recipientEmail = $recipientEmail;
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Récupère l'adresse email du destinataire.
     */
    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    /**
     * Récupère l'objet de l'email.
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Récupère le corps de l'email.
     */
    public function getBody(): string
    {
        return $this->body;
    }
}