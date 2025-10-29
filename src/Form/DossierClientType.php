<?php

namespace App\Form;

use App\Entity\DossierContrat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // Pour les uploads
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File; // Pour la validation des fichiers

class DossierClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('detailsManifestation', TextareaType::class, [
                'label' => 'Détails de la manifestation',
                'required' => false, // Adaptez selon vos besoins
                'attr' => ['rows' => 5],
            ])
            ->add('besoinsTechniques', TextareaType::class, [
                'label' => 'Besoins techniques spécifiques',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('planSecuriteFile', FileType::class, [ // Champ pour l'upload
                'label' => 'Plan de sécurité (PDF)',
                'mapped' => false, // Ne pas lier directement à l'entité
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => ['application/pdf', 'application/x-pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
            ])
             ->add('assuranceFile', FileType::class, [ // Champ pour l'upload
                'label' => 'Attestation d\'assurance (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => ['application/pdf', 'application/x-pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
            ])
            // Ajoutez d'autres champs si nécessaire
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierContrat::class,
        ]);
    }
}