<?php

namespace App\Form;

use App\Entity\DossierContrat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType; // Pour une case à cocher
use Symfony\Component\Form\Extension\Core\Type\TextareaType; // Pour les commentaires
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierMairieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // On pourrait afficher certains champs du client en lecture seule
            // ->add('detailsManifestation', TextareaType::class, [
            //     'label' => 'Détails (Info)',
            //     'disabled' => true, // Non modifiable par la Mairie
            //     'attr' => ['rows' => 3],
            // ])

            // Champs spécifiques à la Mairie
            ->add('commentaireMairie', TextareaType::class, [ // Supposons qu'on ajoute ce champ à DossierContrat
                'label' => 'Commentaires / Avis de la Mairie',
                'required' => false,
                'attr' => ['rows' => 4],
                // IMPORTANT: Ce champ n'existe pas encore dans DossierContrat.
                // Il faudra l'ajouter avec 'make:entity --regenerate' ou manuellement,
                // et mettre 'mapped' => false si vous ne voulez pas le stocker directement.
                // Pour cet exemple, on suppose qu'il existe.
                 'mapped' => false, // Temporaire, le temps d'ajouter le champ à l'entité
            ])
            ->add('validationMairie', CheckboxType::class, [ // Supposons qu'on ajoute ce champ à DossierContrat
                'label' => 'J\'approuve ce dossier (validation Mairie)',
                'required' => true, // La mairie doit cocher pour valider
                // IMPORTANT: Idem, ce champ doit être ajouté à DossierContrat.
            ])
            // Ajoutez d'autres champs si la Mairie doit remplir/uploader des documents spécifiques
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierContrat::class,
        ]);
    }
}