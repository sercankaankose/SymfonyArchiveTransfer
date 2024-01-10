<?php

namespace App\Form;

use App\Entity\Journal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class JournalFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off',

                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Lütfen dergi adı giriniz.',
                    ]),
                ],
            ])
            ->add('issn', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off',

                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d{4}-\d{4}$/',
                        'message' => 'Lütfen geçerli bir Issn Giriniz (örneğin: 1234-5678).',
                    ]),
                ],
            ])
            ->add('eIssn', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off',

                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d{4}-\d{4}$/',
                        'message' => 'Lütfen geçerli bir E-Issn Giriniz (örneğin: 1234-5678).',
                    ]),
                ],
            ]);


        $builder
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                $issn = $data->getIssn();
                $eIssn = $data->getEIssn();

                if (empty($issn) && empty($eIssn)) {
                    $form->get('eIssn')->addError(new FormError('Lütfen Issn veya E-Issn giriniz.'));
                    $form->get('issn')->addError(new FormError('Lütfen Issn veya E-Issn giriniz.'));
                } else {
                    if (!empty($issn)) {
                        $existingIssn = $this->entityManager->getRepository(Journal::class)->findOneBy(['issn' => $issn]);
                        if ($existingIssn && $existingIssn->getId() !== $data->getId()) {
                            $form->get('issn')->addError(new FormError('Bu Issn zaten kullanılmaktadır.'));
                        }
                    }

                    if (!empty($eIssn)) {
                        $existingEissn = $this->entityManager->getRepository(Journal::class)->findOneBy(['e_issn' => $eIssn]);
                        if ($existingEissn && $existingEissn->getId() !== $data->getId()) {
                            $form->get('eIssn')->addError(new FormError('Bu E-Issn zaten kullanılmaktadır.'));
                        }
                    }
                }
            });


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journal::class,
        ]);
    }

}