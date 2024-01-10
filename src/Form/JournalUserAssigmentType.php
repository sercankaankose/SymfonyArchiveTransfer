<?php

namespace App\Form;

use App\Entity\Journal;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalUserAssigmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Dergi', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'form_Dergi'
                ],
            ])
            ->add('Kullanici', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'form_Kullanici'
                ],
            ])
            ->add('ROLE_EDITOR', CheckboxType::class, [
                'label' => 'EDİTÖR',
                'required' => false,
            ])
            ->add('ROLE_OPERATOR', CheckboxType::class, [
                'label' => 'Operatör',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }
}
