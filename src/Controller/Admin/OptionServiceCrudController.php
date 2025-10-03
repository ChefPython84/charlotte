<?php
namespace App\Controller\Admin;

use App\Entity\OptionService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class OptionServiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OptionService::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('nom'),
            MoneyField::new('prix')->setCurrency('EUR'),
            TextareaField::new('description')->hideOnIndex(),
            AssociationField::new('reservations')->onlyOnDetail(),
        ];
    }
}
