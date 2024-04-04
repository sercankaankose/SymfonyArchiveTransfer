<?php

namespace App\Form;

use App\Entity\Authors;
use Doctrine\DBAL\Types\IntegerType;
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
                'attr' => ['class' => 'form-control col half',
                    'autocomplete' => 'off',
                ],
                'label'=>'Ad',
                 'constraints' => [
        new NotBlank(['message' => 'Ad alanı boş olamaz.']),
    ],
            ])
            ->add('lastname', TextType::class, [
                'attr' => ['class' => 'form-control col half',
                    'autocomplete' => 'off',
                ],
                'label'=>'Soyad',
                 'constraints' => [
        new NotBlank(['message' => 'Soyad boş olamaz.']),
    ],
            ])
            ->add('orcId', TextType::class, [
                'attr' => ['class' => 'form-control col half',
                    'autocomplete' => 'off',
                ],
                'label' => 'Orc Id',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/\d{4}-\d{4}-\d{4}-\d{4}$/',
                        'message' => 'Orc ID formatı geçerli değil. Doğru format: 0000-0000-0000-0000',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control col half',
                    'autocomplete' => 'off',
                ],
                'label'=>'E-posta',
                'required' => false,
            ])
            ->add('row', ChoiceType::class, [
                'attr' => ['class' => ''],
                'label'=>'Sıra',
                'choices' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                ],
            ])
            ->add('institute', TextType::class, [
                'attr' => ['class' => 'form-control col trequarter',
                    'autocomplete' => 'off',
                ],
                'label'=>'Kurum',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Authors::class,
        ]);
    }
}
