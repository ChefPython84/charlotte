<?php

namespace App\Controller\Admin;

use App\Entity\Venue;
use App\Entity\Municipality;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class VenueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Venue::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('municipality'),
            TextField::new('name'),
            TextareaField::new('description'),
            IntegerField::new('capacity'),
            TextField::new('address'),
            MoneyField::new('price')->setCurrency('EUR'),
            ArrayField::new('pictures')->hideOnIndex(),
        ];
    }
}
