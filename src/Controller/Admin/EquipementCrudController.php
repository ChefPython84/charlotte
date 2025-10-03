<?php
namespace App\Controller\Admin;

use App\Entity\Equipement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class EquipementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Equipement::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('nom'),
            TextareaField::new('description')->hideOnIndex(),
            AssociationField::new('salles')->onlyOnDetail(),
        ];
    }
}
