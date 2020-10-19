<?php

namespace Kunstmaan\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-group--icon-in-control__form-control',
                ],
            ])
            ->add('_password', PasswordType::class, [
                'attr' => [
                    'class' => 'form-control form-group--icon-in-control__form-control',
                ],
            ])
            ->add('_remember_me', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'checked' => true,
                ],
            ]);
    }

    public function getBlockPrefix()
    {
        return null;
    }
}