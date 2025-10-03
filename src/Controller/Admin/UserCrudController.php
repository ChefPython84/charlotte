<?php
// src/Controller/Admin/UserCrudController.php
namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // adapte selon les propriétés de ton entité User
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom');
        yield TextField::new('prenom');
        yield EmailField::new('email');
        yield TextField::new('telephone')->onlyOnForms();
        // tu stockes un seul role en string -> afficher tel quel
        yield TextField::new('role')->onlyOnIndex();
        yield ChoiceField::new('role')
            ->setChoices([
                'Client' => 'ROLE_CLIENT',
                'Gestionnaire' => 'ROLE_GESTIONNAIRE',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->onlyOnForms();
        yield DateTimeField::new('dateInscription')->onlyOnIndex();
        // si tu veux afficher d'autres champs (siret, commune...) ajoute-les ici
    }
}
