<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('email', TextType::class)
            ->add('telephone', TextType::class)
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Client' => 'client',
                    'Gestionnaire' => 'gestionnaire',
                    'Administrateur' => 'admin',
                ],
                'expanded' => false,
                'multiple' => false,
            ]);

        if (!$isEdit) {
            $builder->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => true,
            ]);
        } else {
            $builder->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'help' => 'Laisser vide pour ne pas changer le mot de passe',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
