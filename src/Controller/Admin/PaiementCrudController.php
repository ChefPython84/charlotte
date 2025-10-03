<?php
namespace App\Controller\Admin;

use App\Entity\Paiement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PaiementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Paiement::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('facture')->setRequired(true),
            DateTimeField::new('datePaiement'),
            MoneyField::new('montant')->setCurrency('EUR'),
            ChoiceField::new('methode')->setChoices([
                'CB' => 'CB',
                'Virement' => 'Virement',
                'PayPal' => 'PayPal',
                'Autre' => 'Autre',
            ]),
            ChoiceField::new('statut')->setChoices([
                'Réussi' => 'réussi',
                'Échoué' => 'échoué',
                'En attente' => 'en attente',
            ]),
        ];
    }
}
