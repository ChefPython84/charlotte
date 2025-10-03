<?php
// src/Controller/Admin/SalleCrudController.php
namespace App\Controller\Admin;

use App\Entity\Salle;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class SalleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Salle::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield TextField::new('nom');
        yield TextareaField::new('description')->hideOnIndex();
        yield IntegerField::new('capacite');
        yield MoneyField::new('prixJour')->setCurrency('EUR');
        yield MoneyField::new('prixHeure')->setCurrency('EUR');

        yield TextField::new('adresse')->onlyOnForms();
        yield TextField::new('ville');
        yield TextField::new('codePostal')->onlyOnForms();

        yield ChoiceField::new('statut')
            ->setChoices([
                'Disponible' => 'disponible',
                'Indisponible' => 'indisponible',
                'Maintenance' => 'maintenance',
            ]);

        // relations
        yield AssociationField::new('equipements')->onlyOnForms();
        yield AssociationField::new('photos')->onlyOnDetail();
        yield AssociationField::new('disponibilites')->onlyOnDetail();
        yield AssociationField::new('avis')->onlyOnDetail();
    }
}
