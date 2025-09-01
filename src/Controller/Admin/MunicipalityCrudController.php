<?php

namespace App\Controller\Admin;

use App\Entity\Municipality;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MunicipalityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Municipality::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextField::new('address'),
            TextField::new('email'),
            TextField::new('phone'),
        ];
    }
}
