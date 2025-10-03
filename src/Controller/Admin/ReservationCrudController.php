<?php
namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('salle'),
            AssociationField::new('user')->hideOnIndex(),
            DateTimeField::new('dateDebut'),
            DateTimeField::new('dateFin'),
            ChoiceField::new('statut')->setChoices([
                'Option' => 'option',
                'Dossier en attente' => 'dossier_en_attente',
                'En attente' => 'en_attente',
                'Confirmée' => 'confirme',
                'Annulée' => 'annule',
            ]),
            MoneyField::new('prixTotal')->setCurrency('EUR'),
            TextField::new('typeManifestation')->hideOnIndex(),
            ArrayField::new('vacations')->hideOnIndex(),
            ArrayField::new('documents')->onlyOnDetail(),
            DateTimeField::new('dossierSubmittedAt')->onlyOnDetail(),
            DateTimeField::new('contractCreatedAt')->onlyOnDetail(),
            DateTimeField::new('contractSignedAt')->onlyOnDetail(),
            AssociationField::new('factures')->onlyOnDetail(),
        ];
    }
}
