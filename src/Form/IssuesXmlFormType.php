<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class IssuesXmlFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('xml', FileType::class, [
                'required' => true,

                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Lütfen Bir Xml Giriniz.']),

                    new File([
                        'maxSize' => '10240k',
                        'mimeTypes' => [
                            'text/xml',
                            'application/xml',
                        ],
                        'mimeTypesMessage' => 'Lütfen geçerli bir XML Dosyası Yükleyiniz.',
                    ]),
                ],
            ]);        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
