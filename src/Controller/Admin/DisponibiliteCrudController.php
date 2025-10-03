<?php
namespace App\Controller\Admin;

use App\Entity\Disponibilite;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class DisponibiliteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Disponibilite::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('salle'),
            DateTimeField::new('dateDebut'),
            DateTimeField::new('dateFin'),
            TimeField::new('heureDebut')->hideOnIndex(),
            TimeField::new('heureFin')->hideOnIndex(),
            ChoiceField::new('statut')->setChoices([
                'Libre' => 'libre',
                'Réservé' => 'reserve',
            ]),
        ];
    }
}
