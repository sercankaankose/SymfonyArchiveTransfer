<?php

namespace App\Form;

use App\Entity\User;
use App\Validator\Constraints\PasswordPolicy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;

class ChangePasswordFormType extends AbstractType
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mevcut Şifre', 'attr' => [
                    'class' => 'form-control'
                ],
                'required' => true,

                'constraints' => [
                    new NotBlank(['message' => 'Lütfen Mevcut Şifrenizi Giriniz',]),
//                    new YourCustomPasswordValidation($this->passwordHasher),

                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Yeni Girilen Şifre Eşleşmeli',
                'options' => [
                    'attr' => ['class' => 'password-field form-control'],
                ],
                'required' => true,
                'first_options' => [
                    'required' => true,
                    'label' => 'Yeni Şifre',
                ],
                'second_options' => [
                    'label' => 'Yeni Şifre Tekrar',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Lütfen Bir Şifre Giriniz',
                    ]),

                    new Length([
                        'min' => 6,
                        'minMessage' => 'Şifreniz En az {{ limit }} karakter olmalı',
                        'max' => 20,
                        'maxMessage' => 'Şifreniz {{ limit }} karakterden fazla olamaz.',

                    ]),
                    new PasswordPolicy([
                        'message' => 'Şifre En az 1 harf ve 1 sayı içermelidir',
                    ]),
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }
}

class YourCustomPasswordValidation extends ConstraintValidator
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function validate($value, Constraint $constraint)
    {//çalışmıyor
        $user = $this->context->getObject()->getUser();

        if (!$this->passwordHasher->isPasswordValid($user, $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

//    public function validateCurrentPassword($currentPassword, ExecutionContextInterface $context)
//    {
//        $form = $context->getObject();
//        $currentUser = $form->getConfig()->getOptions()['current_user'];
//
//        if (!$this->passwordHasher->isPasswordValid($currentUser, $currentPassword)) {
//            $context->buildViolation('Mevcut şifrenizi yanlış girdiniz.')
//                ->atPath('currentPassword')
//                ->addViolation();
//        }
//    }



