<?php
// src/Controller/Admin/UserCrudController.php
namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
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
        yield TextField::new('telephone')->onlyOnForms()->hideOnIndex();

        // Afficher le rôle actuel sur l'index
        yield TextField::new('role', 'Rôle')->onlyOnIndex();

        // MODIFIÉ : Utilise les constantes de l'entité User
        yield ChoiceField::new('role', 'Rôle')
            ->setChoices([
                'Client' => User::ROLE_CLIENT,
                'Gestionnaire' => User::ROLE_GESTIONNAIRE,
                'Mairie' => User::ROLE_MAIRIE, // NOUVELLE OPTION
                'Administrateur' => User::ROLE_ADMIN,
            ])
            ->onlyOnForms(); // Affiché seulement dans les formulaires 'new' et 'edit'

        yield DateTimeField::new('dateInscription')->onlyOnIndex();
        
        // J'ajoute vos champs spécifiques (ils étaient dans mon historique)
        yield TextField::new('typeOrganisateur')->hideOnIndex();
        yield TextField::new('siret')->hideOnIndex();
        yield TextField::new('rna')->hideOnIndex();
        yield TextField::new('commune')->hideOnIndex();
    }
}