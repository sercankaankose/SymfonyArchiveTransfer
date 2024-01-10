<?php

namespace App\Form;

use App\Entity\Authors;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AuthorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'attr' => ['class' => 'form-control col half'],
                'label'=>'Ad',
                 'constraints' => [
        new NotBlank(['message' => 'Ad alanı boş olamaz.']),
    ],
            ])
            ->add('lastname', TextType::class, [
                'attr' => ['class' => 'form-control col half'],
                'label'=>'Soyad',
                 'constraints' => [
        new NotBlank(['message' => 'Soyad boş olamaz.']),
    ],
            ])
            ->add('orcId', TextType::class, [
                'attr' => ['class' => 'form-control col half'],
                'label' => 'Orc Id',
                'constraints' => [
                    new NotBlank(['message' => 'Orc ID alanı boş olamaz.']),
                    new Regex([
                        'pattern' => '/\d{4}-\d{4}-\d{4}-\d{4}$/',
                        'message' => 'Orc ID formatı geçerli değil. Doğru format: 0000-0000-0000-0000',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control col half'],
                'label'=>'E-posta',
                'constraints' => [
                    new NotBlank(['message' => 'E-posta alanı boş olamaz.']),
                ],
            ])
            ->add('institute', TextType::class, [
                'attr' => ['class' => 'form-control col trequarter'],
                'label'=>'Kurum',
                'constraints' => [
                    new NotBlank(['message' => 'Kurum alanı boş olamaz.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Authors::class,
        ]);
    }
}
