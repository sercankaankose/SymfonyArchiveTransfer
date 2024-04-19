<?php


namespace App\Form;

use App\Entity\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class TranslatorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'attr' => ['class' => 'form-control col half'],
                'label' => 'Ad',
                'required' => true,



            ])
            ->add('lastname', TextType::class, [
                'attr' => ['class' => 'form-control col half'],
                'label' => 'Soyad',
                'required' => true,
            ])
            ->add('orcId', TextType::class, [
                'attr' => ['class' => 'form-control col half'],
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
                'attr' => ['class' => 'form-control col half'],
                'label' => 'E-posta',
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
                'attr' => ['class' => 'form-control col trequarter'],
                'label' => 'Kurum',
                'required' => false,


            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Translator::class,
        ]);
    }
}
