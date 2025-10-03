<?php
namespace App\Controller\Admin;

use App\Entity\PhotoSalle;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class PhotoSalleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhotoSalle::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('salle'),
            TextField::new('url')->setHelp('URL de l\'image (ou adapter pour upload)'),
            TextareaField::new('description')->hideOnIndex(),
            BooleanField::new('isPrincipale'),
            DateTimeField::new('uploadedAt')->onlyOnIndex(),
        ];
    }
}
