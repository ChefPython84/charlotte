<?php
// src/Controller/Admin/ReservationCrudController.php
namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Entity\Notification; // <-- AJOUTER L'ENTITÉ NOTIFICATION
use App\Message\SendContractNotification; 
use Doctrine\ORM\EntityManagerInterface; 
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField; 
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
        yield AssociationField::new('factures')->onlyOnDetail();
    }
    
    /**
     * Cette méthode est appelée par EasyAdmin juste avant de sauvegarder
     * une entité qui a été MODIFIÉE.
     */
     public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Reservation $reservation */
        $reservation = $entityInstance;

        // 1. Récupère l'état AVANT la modification (pour comparer)
        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($reservation);
        $originalStatut = $originalData['statut'] ?? null;
        $newStatut = $reservation->getStatut();

        // 2. Sauvegarde l'entité (le statut est mis à jour en BDD)
        parent::updateEntity($entityManager, $entityInstance);

        // 3. --- DÉBUT LOGIQUE DE NOTIFICATION ---
        // On vérifie si le statut a réellement changé
        if ($originalStatut !== $newStatut) {
            $message = null;
            $userToNotify = $reservation->getUser();

            // On crée un message basé sur le NOUVEAU statut
            // ✍️ Personnalisez ces messages !
            switch ($newStatut) {
                case 'attente_dossier_client':
                    $message = "L'administration requiert des documents pour votre réservation '{$reservation->getSalle()->getNom()}'.";
                    break;
                case 'contrat_genere':
                    $message = "Votre contrat pour '{$reservation->getSalle()->getNom()}' est prêt et en attente de votre signature.";
                    break;
                case 'contrat_signe':
                    $message = "Le contrat pour '{$reservation->getSalle()->getNom()}' a été signé par l'administration, votre réservation est confirmée.";
                    break;
                case 'annulee':
                    $message = "Votre réservation pour '{$reservation->getSalle()->getNom()}' a été annulée par l'administration.";
                    break;
                // ... Ajoutez d'autres 'case' pour les statuts pertinents
            }

            // Si on a un message et un utilisateur à notifier...
            if ($message && $userToNotify) {
                $notification = new Notification();
                $notification->setUser($userToNotify);
                $notification->setMessage($message);
                
                // On persiste la NOUVELLE notification
                $entityManager->persist($notification);
                
                // On flush() une seconde fois (la première était dans parent::updateEntity)
                // pour sauvegarder la notification.
                $entityManager->flush();
            }
            
            // 4. (Votre ancienne logique d'envoi d'email via Messenger est ci-dessous)
            // (Vous pouvez la garder ou la supprimer si elle fait doublon)
            $send = false;
            $recipientEmail = null;
            // ... (votre code existant pour SendContractNotification)
            // ...
        }
        // --- FIN LOGIQUE DE NOTIFICATION ---
    }
}