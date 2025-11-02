<?php
namespace App\Controller\Admin;

use App\Entity\Facture;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class FactureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Facture::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('reservation', 'Réservation')
                ->autocomplete(),
            MoneyField::new('montant')->setCurrency('EUR'),
            ChoiceField::new('statut')->setChoices([
                'Payée' => 'payée',
                'En attente' => 'en attente',
                'Annulée' => 'annulée',
            ]),
            DateTimeField::new('dateFacture'),
            AssociationField::new('paiements')->onlyOnDetail(),
        ];
    }
}
