<?php
// src/Controller/Admin/ReservationCrudController.php
namespace App\Controller\Admin;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface; // <-- AJOUT
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField; 
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext; // <-- AJOUT
use Symfony\Component\HttpFoundation\RedirectResponse; // <-- AJOUT
use Symfony\Component\Workflow\Registry as WorkflowRegistry;


class ReservationCrudController extends AbstractCrudController
{
    // <-- MODIFIÉ : Injection des services requis (Workflow, EntityManager)
    // MessageBusInterface a été retiré, car les notifications sont gérées par le Subscriber
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private WorkflowRegistry $workflowRegistry,
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    // --- MODIFIÉ : Ajout des actions pour piloter le workflow ---
    public function configureActions(Actions $actions): Actions
    {
        // Action existante pour voir le chat
        $goToChat = Action::new('goToChat', 'Voir / Répondre', 'fa fa-comments')
            ->linkToRoute('contrat_tunnel', function (Reservation $reservation) {
                return ['id' => $reservation->getId()];
            })
            ->setHtmlAttributes(['target' => '_blank']);

        // --- DÉBUT DES NOUVELLES ACTIONS DE WORKFLOW ---

        // Transition : admin_demande_dossier
        $demanderDossier = Action::new('demanderDossier', 'Demander Dossier', 'fa fa-file-import')
            ->linkToCrudAction('applyTransition')
            ->setQueryParameter('transition', 'admin_demande_dossier')
            ->addCssClass('btn btn-secondary')
            ->displayIf(fn (Reservation $reservation) => 
                $this->workflowRegistry->get($reservation)->can($reservation, 'admin_demande_dossier')
                && $this->isGranted('ROLE_GESTIONNAIRE') // Sécurisé par rôle
            );

        // Transition : loueur_valide_dossier
        $validerLoueur = Action::new('validerLoueur', 'Valider (Loueur)', 'fa fa-user-check')
            ->linkToCrudAction('applyTransition')
            ->setQueryParameter('transition', 'loueur_valide_dossier')
            ->addCssClass('btn btn-success')
            ->displayIf(fn (Reservation $reservation) => 
                $this->workflowRegistry->get($reservation)->can($reservation, 'loueur_valide_dossier')
                && $this->isGranted('loueur_valide_dossier', $reservation) // Sécurisé par Voter
            );

        // Transition : mairie_valide_dossier
        $validerMairie = Action::new('validerMairie', 'Valider (Mairie)', 'fa fa-university')
            ->linkToCrudAction('applyTransition')
            ->setQueryParameter('transition', 'mairie_valide_dossier')
            ->addCssClass('btn btn-success')
            ->displayIf(fn (Reservation $reservation) => 
                $this->workflowRegistry->get($reservation)->can($reservation, 'mairie_valide_dossier')
                && $this->isGranted('mairie_valide_dossier', $reservation) // Sécurisé par Voter
            );

        // Transition : prestataire_valide_dossier
        $validerPrestataire = Action::new('validerPrestataire', 'Valider (Prestataire)', 'fa fa-concierge-bell')
            ->linkToCrudAction('applyTransition')
            ->setQueryParameter('transition', 'prestataire_valide_dossier')
            ->addCssClass('btn btn-success')
            ->displayIf(fn (Reservation $reservation) => 
                $this->workflowRegistry->get($reservation)->can($reservation, 'prestataire_valide_dossier')
                && $this->isGranted('prestataire_valide_dossier', $reservation) // Sécurisé par Voter
            );

        // Transition : annuler
        $annulerReservation = Action::new('annuler', 'Annuler', 'fa fa-ban')
            ->linkToCrudAction('applyTransition')
            ->setQueryParameter('transition', 'annuler')
            ->addCssClass('btn btn-danger')
            ->displayIf(fn (Reservation $reservation) => 
                $this->workflowRegistry->get($reservation)->can($reservation, 'annuler')
                && $this->isGranted('ROLE_GESTIONNAIRE')
            );

        // --- FIN DES NOUVELLES ACTIONS DE WORKFLOW ---

        return $actions
            // Ajoute le bouton "Voir / Répondre"
            ->add(Crud::PAGE_INDEX, $goToChat)
            ->add(Crud::PAGE_DETAIL, $goToChat)

            // Ajoute les boutons de workflow
            ->add(Crud::PAGE_INDEX, $demanderDossier)
            ->add(Crud::PAGE_INDEX, $validerLoueur)
            ->add(Crud::PAGE_INDEX, $validerMairie)
            ->add(Crud::PAGE_INDEX, $validerPrestataire)
            ->add(Crud::PAGE_INDEX, $annulerReservation)
        ;
    }

    /**
     * NOUVELLE MÉTHODE : Gère l'application d'une transition de workflow
     */
    public function applyTransition(AdminContext $context): RedirectResponse
    {
        /** @var Reservation $reservation */
        $reservation = $context->getEntity()->getInstance();
        $transition = $context->getRequest()->query->get('transition');

        if (!$transition) {
            $this->addFlash('danger', 'Transition non spécifiée.');
            return $this->redirect($context->getReferrer());
        }

        try {
            // Sécurisation (via Voter, si configuré)
            $this->denyAccessUnlessGranted($transition, $reservation);

            $workflow = $this->workflowRegistry->get($reservation);

            if ($workflow->can($reservation, $transition)) {
                // Applique la transition
                $workflow->apply($reservation, $transition);
                
                // Sauvegarde le changement d'état
                $this->entityManager->flush(); 
                
                $this->addFlash('success', "La transition '$transition' a été appliquée.");
            } else {
                $this->addFlash('warning', "La transition '$transition' ne peut pas être appliquée.");
            }

        } catch (\Exception $e) {
            $this->addFlash('danger', "Erreur lors de la transition : " . $e->getMessage());
        }

        return $this->redirect($context->getReferrer());
    }


    public function configureFields(string $pageName): iterable
    {
        // Champ "pseudo-champ" pour le chat (inchangé)
        yield TextField::new('commentaires')
            ->setLabel('Échanges sur le dossier')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/commentaires.html.twig');

        // Champs de base (inchangés)
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('salle');
        yield AssociationField::new('user')->hideOnIndex();
        yield DateTimeField::new('dateDebut');
        yield DateTimeField::new('dateFin');

        // --- MODIFIÉ : Le champ 'statut' n'est plus modifiable, il est en lecture seule ---
        yield ChoiceField::new('statut')
            ->setLabel('Statut Actuel')
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
            ])
            ->setDisabled(true); // <-- Modification clé : rend le champ non-éditable
            
        // Champs restants (inchangés)
        yield MoneyField::new('prixTotal')->setCurrency('EUR')->hideOnIndex();
        yield TextField::new('typeManifestation')->hideOnIndex();
        yield AssociationField::new('factures')->onlyOnDetail();
    }
    
     /**
      * --- SUPPRIMÉ ---
      * La méthode updateEntity() a été supprimée.
      * Toute la logique de notification est désormais gérée par
      * App\EventSubscriber\ReservationWorkflowSubscriber
      * pour garantir qu'elle ne se déclenche que lors de transitions
      * de workflow valides, et non lors de changements manuels.
      */
}