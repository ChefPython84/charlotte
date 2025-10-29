<?php
// src/Controller/Admin/ReservationCrudController.php
namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Message\SendContractNotification; 
use Doctrine\ORM\EntityManagerInterface; 
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField; // Assurez-vous d'avoir ce use
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField; // Ajoutez si vous avez d'autres champs Text
use Symfony\Component\Messenger\MessageBusInterface; 

class ReservationCrudController extends AbstractCrudController
{
    private MessageBusInterface $bus; 

    public function __construct(MessageBusInterface $bus) 
    {
        $this->bus = $bus;
    }

    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('salle');
        yield AssociationField::new('user')->hideOnIndex();
        yield DateTimeField::new('dateDebut');
        yield DateTimeField::new('dateFin');

        // VÉRIFIEZ CES VALEURS EXACTEMENT
        yield ChoiceField::new('statut')
            ->setChoices([
                // Libellé affiché => Valeur enregistrée (doit correspondre au workflow.yaml)
                'En attente (Demande client)' => 'en_attente',
                'Attente Dossier Client' => 'attente_dossier_client',
                'Attente Validation Loueur' => 'attente_validation_loueur',
                'Attente Validation Mairie' => 'attente_validation_mairie',
                'Attente Validation Prestataire' => 'attente_validation_prestataire',
                'Contrat Généré' => 'contrat_genere',
                'Contrat Signé' => 'contrat_signe',
                'Annulée' => 'annulee', // Sans accent, 'ee'
            ]);
            
        yield MoneyField::new('prixTotal')->setCurrency('EUR')->hideOnIndex();
        yield TextField::new('typeManifestation')->hideOnIndex();
        // Ajoutez d'autres champs ici si nécessaire (vacations, documents...)
        yield AssociationField::new('factures')->onlyOnDetail();
    }
    
    // Gardez la méthode updateEntity si vous l'avez ajoutée pour déclencher les emails depuis EasyAdmin
    // Assurez-vous que les conditions (if $newStatut === ...) utilisent les noms corrigés.
     public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Reservation $reservation */
        $reservation = $entityInstance;

        // Récupère l'état AVANT la modification
        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($reservation);
        $originalStatut = $originalData['statut'] ?? null;
        $newStatut = $reservation->getStatut();

        parent::updateEntity($entityManager, $entityInstance); // Sauvegarde l'entité

        // Si le statut a changé via EasyAdmin, on envoie l'email correspondant MANUELLEMENT
        if ($originalStatut !== $newStatut) {
            $recipientEmail = null;
            $subject = null;
            $messageBody = null;
            $send = false; // Flag pour savoir si on doit envoyer

            // --- Logique copiée et adaptée du ReservationWorkflowSubscriber ---
            // Exemple : Le statut passe à attente_dossier_client (via EasyAdmin)
             if ($newStatut === 'attente_dossier_client') {
                 $recipientEmail = $reservation->getUser()?->getEmail(); // Notifier le client
                 if ($recipientEmail) {
                     $subject = "Votre dossier pour la réservation #" . $reservation->getId() . " est requis";
                     $messageBody = "Bonjour... Veuillez compléter votre dossier ici : [Lien]";
                     $send = true;
                 }
            }
            // Exemple : Le statut passe à attente_validation_loueur
            elseif ($newStatut === 'attente_validation_loueur') {
                $recipientEmail = 'admin@votresite.com'; // **À REMPLACER**
                $subject = "Dossier soumis pour réservation #" . $reservation->getId();
                $messageBody = "Le client a soumis son dossier...";
                $send = true;
            }
            // Exemple : Le statut passe à attente_validation_mairie
            elseif ($newStatut === 'attente_validation_mairie') {
                $recipientEmail = 'mairie@votresite.com'; // **À REMPLACER**
                $subject = "Validation Mairie requise pour réservation #" . $reservation->getId();
                $messageBody = "Le dossier attend votre validation...";
                $send = true;
            }
            // ... Ajoutez les autres statuts du workflow.yaml ...

            // --- Envoi via Messenger si un email a été défini ---
            if ($send && $recipientEmail && $subject && $messageBody) {
                $emailMessage = new SendContractNotification($recipientEmail, $subject, $messageBody);
                $this->bus->dispatch($emailMessage);
                 $this->addFlash('info', 'Notification email envoyée.'); // Info pour l'admin
            }
        }
    }
}