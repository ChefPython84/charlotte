<?php
// src/Controller/Admin/UserCrudController.php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

// --- Imports pour l'envoi d'email ---
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

// --- Imports pour l'Action personnalisée ---
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator; // <-- AJOUT POUR CORRIGER LE BUG

class UserCrudController extends AbstractCrudController
{
    private ResetPasswordHelperInterface $resetPasswordHelper;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private AdminUrlGenerator $adminUrlGenerator; // <-- AJOUT POUR CORRIGER LE BUG

    public function __construct(
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        AdminUrlGenerator $adminUrlGenerator // <-- AJOUT POUR CORRIGER LE BUG
    ) {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->adminUrlGenerator = $adminUrlGenerator; // <-- AJOUT POUR CORRIGER LE BUG
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // ... (Vos champs restent identiques)
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom');
        yield TextField::new('prenom');
        yield EmailField::new('email');
        yield TextField::new('telephone')->onlyOnForms()->hideOnIndex();

        yield TextField::new('role', 'Rôle')->onlyOnIndex();
        yield ChoiceField::new('role', 'Rôle')
            ->setChoices([
                'Client' => User::ROLE_CLIENT,
                'Gestionnaire' => User::ROLE_GESTIONNAIRE,
                'Mairie' => User::ROLE_MAIRIE,
                'Administrateur' => User::ROLE_ADMIN,
            ])
            ->onlyOnForms();

        yield DateTimeField::new('dateInscription')->onlyOnIndex();
        yield TextField::new('typeOrganisateur')->hideOnIndex();
        yield TextField::new('siret')->hideOnIndex();
        yield TextField::new('rna')->hideOnIndex();
        yield TextField::new('commune')->hideOnIndex();
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendResetLink = Action::new('sendResetLink', 'Envoyer lien reset', 'fa fa-key')
            ->linkToCrudAction('sendResetLink')
            ->setCssClass('btn btn-outline-warning');

        return $actions
            ->add(Crud::PAGE_INDEX, $sendResetLink)
            ->add(Crud::PAGE_DETAIL, $sendResetLink)
            ->add(Crud::PAGE_EDIT, $sendResetLink); // <-- AJOUT DE VOTRE DEMANDE
    }

    /**
     * C'est la méthode appelée par le bouton.
     */
    public function sendResetLink(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();

        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirect($context->getReferrer()); // Referrer est sûr ici car on ne peut pas arriver ici sans
        }
        
        if ($user->getMotDePasse() === null) {
             $this->addFlash('warning', 'Cet utilisateur n\'a pas encore défini son mot de passe initial. L\'e-mail de "Bienvenue" a été renvoyé.');
             $this->sendWelcomeEmail($user);
        } else {
            // 1. Générer le token
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);

            // 2. Générer l'URL
            $url = $this->urlGenerator->generate('app_reset_password', [
                'token' => $resetToken->getToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            // 3. Envoyer l'email de "Réinitialisation" (template "oubli")
            $email = (new TemplatedEmail())
                ->from('ne-pas-repondre@espace1500.fr')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe (Demandé par un admin)')
                ->htmlTemplate('reset_password/email.html.twig') 
                ->context([
                    'resetToken' => $resetToken,
                    'user' => $user,
                    'url' => $url,
                ]);

            $this->mailer->send($email);

            // 4. Confirmer à l'admin
            $this->addFlash('success', 'Email de réinitialisation envoyé à ' . $user->getEmail());
        }
        
        // --- CORRECTION DU BUG ---
        // On crée une URL de secours (fallback) au cas où le 'referrer' serait null
        $fallbackUrl = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        // On redirige vers la page précédente (si elle existe) ou vers le fallback (la liste)
        return $this->redirect($context->getReferrer() ?? $fallbackUrl);
        // --- FIN DE LA CORRECTION ---
    }


    // --- (Logique de création) ---
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $entityInstance;
        parent::persistEntity($entityManager, $entityInstance);

        $this->sendWelcomeEmail($user);

        $this->addFlash('success', 'Utilisateur créé. Un email a été envoyé à ' . $user->getEmail() . ' pour définir son mot de passe.');
    }
    
    /**
     * Fonction privée pour l'email de "Bienvenue"
     */
    private function sendWelcomeEmail(User $user): void
    {
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);

        $url = $this->urlGenerator->generate('app_reset_password', [
            'token' => $resetToken->getToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from('ne-pas-repondre@espace1500.fr') 
            ->to($user->getEmail())
            ->subject('Bienvenue ! Créez votre mot de passe pour Espace 1500')
            ->htmlTemplate('reset_password/new_user_email.html.twig') 
            ->context([
                'resetToken' => $resetToken,
                'user' => $user, 
                'url' => $url, 
            ]);

        $this->mailer->send($email);
    }
}