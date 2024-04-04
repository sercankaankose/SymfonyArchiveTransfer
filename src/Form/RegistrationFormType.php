<?php

namespace App\Form;

use App\Entity\User;
use App\Validator\Constraints\PasswordPolicy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'E-posta Adredi giriniz.',
                    ]),
                    new Regex([
                        'pattern' => '/^.+@.+\..+$/',
                        'message' => 'Lütfen Geçerli Bir Eposta Giriniz.',
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a name.',
                    ]),
                ],
            ])
            ->add('surname', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a surname.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control',
                ],
                'constraints' => [

                    new NotBlank([
                        'message' => 'Lütfen Şifrenizi Giriniz.',
                    ]),

                    new Length([
                        'min' => 5,
                        'minMessage' => 'Şifre en az {{ limit }} karakter içermelidir.',
                        'max' => 15,
                    ]),
                    new PasswordPolicy([
                        'message' => 'Şifreniz en az 1 Harf, 1 Sayı, ve en az 1 tane -, _, . karakterlerinden birini içermelidir.',
                    ]),
                ],
            ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
