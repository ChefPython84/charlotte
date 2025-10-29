<?php

namespace App\Form;

use App\Entity\DossierContrat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierPrestataireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('commentairePrestataire', TextareaType::class, [ // Nouveau champ à ajouter à l'entité
                'label' => 'Commentaires / Validation technique',
                'required' => false,
                'attr' => ['rows' => 4],
                // 'mapped' => false, // On enlèvera après avoir ajouté le champ
            ])
            ->add('validationPrestataire', CheckboxType::class, [ // Nouveau champ à ajouter à l'entité
                'label' => 'J\'approuve les aspects techniques/logistiques',
                'required' => true, // Le prestataire doit cocher pour valider
                // 'mapped' => false, // On enlèvera après avoir ajouté le champ
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierContrat::class,
        ]);
    }
}