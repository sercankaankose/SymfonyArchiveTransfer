<?php

namespace App\Form;

use App\Entity\Issues;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class IssuesFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('year', ChoiceType::class, [
                'required' => true,
                'choices' => array_combine(
                    range(date('Y'), 1900),
                    range(date('Y'), 1900)
                ),
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('volume', TextType::class, [
                'required' => false,

                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Cilt',
                'constraints' => [
                ],
            ])
            ->add('special', CheckboxType::class, [
                'required' => false,

                'attr' => [
                    'class' => 'form-check',
                ],
                'label' => 'Özel Sayı',
                'constraints' => [
                ],
            ])
            ->add('number', TextType::class, [
                'required' => true,

                'constraints' => [
                    new NotBlank([
                        'message' => 'Lütfen Sayı Giriniz.',
                    ]),

                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            ->add('xml', FileType::class, [
                'required' => false,
                'empty_data' => '',

                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10240k',
                        'mimeTypes' => [
                            'text/xml',
                            'application/xml',
                        ],
                        'mimeTypesMessage' => 'Lütfen geçerli bir XML Dosyası Yükleyiniz.',
                    ]),
                ],
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Issues::class,
            'constraints' => [
                new Callback([$this, 'validateUniqueIssue']),
            ],
        ]);
    }
    public function validateUniqueIssue($data, ExecutionContextInterface $context): void
    {
        $existingIssue = $this->entityManager
            ->getRepository(Issues::class)
            ->findOneBy([
                'journal' => $data->getJournal(),
                'year' => $data->getYear(),
                'number' => $data->getNumber(),
            ]);

        if ($existingIssue && $existingIssue !== $data) {
            $context->buildViolation('Bu yıl, sayı ve dergi  zaten mevcut.')
                ->atPath('year')
                ->addViolation();
        }
    }
}
