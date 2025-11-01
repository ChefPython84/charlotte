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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; // <-- AJOUTER

class ReservationCrudController extends AbstractCrudController
{
    private MessageBusInterface $bus; 
    private UrlGeneratorInterface $urlGenerator; // <-- AJOUTER

    public function __construct(
        MessageBusInterface $bus, 
        UrlGeneratorInterface $urlGenerator // <-- AJOUTER
    ) {
        $this->bus = $bus;
        $this->urlGenerator = $urlGenerator; // <-- AJOUTER
    }

    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // (Cette fonction reste INCHANGÉE)
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('salle');
        yield AssociationField::new('user')->hideOnIndex();
        yield DateTimeField::new('dateDebut');
        yield DateTimeField::new('dateFin');

        yield ChoiceField::new('statut')
            ->setChoices([
                // Libellé affiché => Valeur enregistrée
                'En attente (Demande client)' => 'en_attente',
                'Attente Dossier Client' => 'attente_dossier_client',
                'Attente Validation Loueur' => 'attente_validation_loueur',
                'Attente Validation Mairie' => 'attente_validation_mairie',
                'Attente Validation Prestataire' => 'attente_validation_prestataire',
                'Contrat Généré' => 'contrat_genere',
                'Contrat Signé' => 'contrat_signe',
                'Annulée' => 'annulee', 
            ]);
            
        yield MoneyField::new('prixTotal')->setCurrency('EUR')->hideOnIndex();
        yield TextField::new('typeManifestation')->hideOnIndex();
        yield AssociationField::new('factures')->onlyOnDetail();
    }
    
     public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Reservation $reservation */
        $reservation = $entityInstance;

        // 1. Récupère l'état AVANT la modification
        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($reservation);
        $originalStatut = $originalData['statut'] ?? null;
        $newStatut = $reservation->getStatut();

        // 2. Sauvegarde l'entité
        parent::updateEntity($entityManager, $entityInstance);

        // 3. Logique de Notification
        if ($originalStatut !== $newStatut) {
            $message = null;
            $userToNotify = $reservation->getUser();
            
            // --- MODIFICATION : On génère le lien ---
            $link = $this->urlGenerator->generate('contrat_tunnel', [
                'id' => $reservation->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL); // Assure un lien complet

            // ✍️ Personnalisez vos messages ici
            switch ($newStatut) {
                case 'attente_dossier_client':
                    $message = "Action requise pour votre réservation '{$reservation->getSalle()->getNom()}'.";
                    break;
                case 'contrat_genere':
                    $message = "Votre contrat pour '{$reservation->getSalle()->getNom()}' est prêt à être signé.";
                    break;
                case 'contrat_signe':
                    $message = "Contrat signé ! Votre réservation '{$reservation->getSalle()->getNom()}' est confirmée.";
                    break;
                case 'annulee':
                    $message = "Votre réservation '{$reservation->getSalle()->getNom()}' a été annulée.";
                    break;
            }

            if ($message && $userToNotify) {
                $notification = new Notification();
                $notification->setUser($userToNotify);
                $notification->setMessage($message);
                $notification->setLink($link); // <-- On ajoute le lien
                
                $entityManager->persist($notification);
                $entityManager->flush(); // Flush pour sauvegarder la notif
            }
             $send = false;
            $recipientEmail = null;
        }
    }
}