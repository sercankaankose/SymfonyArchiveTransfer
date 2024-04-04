<?php

namespace App\Form;

use App\Entity\Citations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CitationsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('referance', TextType::class, [
                'attr' => ['class' => 'form-control col bigger'],
                'label' => 'Referans'
            ])
            ->add('row', TextType::class, [
                'attr' => ['class' => 'form-control col small'],
                'label' => 'Numara'
            ])
            ->add('citationsText', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'style' => 'width: 100%; height: 650px; overflow-y: scroll;'],
                'label' => 'Atıf ',
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Citations::class,
        ]);
    }
}
