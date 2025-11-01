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

// --- AJOUTS IMPORTANTS ---
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
// --- FIN DES AJOUTS ---

class UserCrudController extends AbstractCrudController
{
    // 1. Injecter les services dont nous avons besoin
    private ResetPasswordHelperInterface $resetPasswordHelper;
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager
        // Note: Le PasswordHasher n'est plus nécessaire ici
    ) {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom');
        yield TextField::new('prenom');
        yield EmailField::new('email');

        // 2. ON RETIRE LE CHAMP MOT DE PASSE
        // yield PasswordField::new('mot_de_passe', 'Mot de passe') ...

        yield TextField::new('telephone')->onlyOnForms()->hideOnIndex();

        // ... (le reste de vos champs : rôle, etc.) ...
        
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

    // 3. On ne hash plus rien, on ENVOIE L'EMAIL
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $entityInstance;

        // On sauvegarde l'utilisateur (avec mdp = null)
        parent::persistEntity($entityManager, $entityInstance);

        // On génère le token de "création" de mot de passe
        // Le bundle le stocke en BDD (dans la table reset_password_request)
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);

        // On crée l'URL que l'utilisateur recevra
        // 'app_reset_password' est le nom de la route générée par make:reset-password
        $url = $this->generateUrl('app_reset_password', [
            'token' => $resetToken->getToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // On envoie l'email
        $email = (new TemplatedEmail())
            ->from('ne-pas-repondre@votresite.com')
            ->to($user->getEmail())
            ->subject('Finalisez la création de votre compte Espace 1500')
            // Le template a été créé par la commande make:
            ->htmlTemplate('reset_password/email.html.twig') 
            ->context([
                'resetToken' => $resetToken,
                'user' => $user,
                'url' => $url, // On passe l'URL au template
            ]);

        $this->mailer->send($email);

        $this->addFlash('success', 'Utilisateur créé. Un email a été envoyé à ' . $user->getEmail() . ' pour définir son mot de passe.');
    }

    // 4. On supprime la logique de hash de la MISE À JOUR
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // On se contente de sauvegarder les changements (ex: changement de rôle)
        // On ne touche plus au mot de passe ici.
        parent::updateEntity($entityManager, $entityInstance);
    }
}