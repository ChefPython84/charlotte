<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ComptePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => ['autocomplete' => 'current-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre mot de passe actuel.',
                    ]),
                    new UserPassword([
                        'message' => 'Le mot de passe actuel n\'est pas valide.',
                    ]),
                ],
                'mapped' => false, // Non lié à l'entité
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'label' => 'Nouveau mot de passe',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez entrer un nouveau mot de passe.',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                            'max' => 4096,
                        ]),
                        // Optionnel : forcer un mot de passe robuste
                        // new PasswordStrength([
                        //     'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        //     'message' => 'Le mot de passe n\'est pas assez robuste.'
                        // ])
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmez le nouveau mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les champs du nouveau mot de passe doivent correspondre.',
                'mapped' => false, // Non lié à l'entité
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}