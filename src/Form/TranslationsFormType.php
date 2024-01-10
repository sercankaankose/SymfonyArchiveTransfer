<?php

namespace App\Form;

use App\Entity\Translations;
use App\Form\DataTransformer\ArrayToStringTransformer;
use phpDocumentor\Reflection\Types\False_;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TranslationsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', ChoiceType::class, [
                'attr' => ['class' => 'form-control col small'],
                'label' => 'Dil',
                'required' => true,
                'choices' => [
                    'Türkçe' => '001',
                    'İngilizce' => '002',
                    'Almanca' => '003',
                    'İspanyolca' => '004',
                    'Arapça' => '005',
                    'Rusça' => '006',
                    'Farsça' => '007',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Dil alanı boş olamaz.']),
                ],
            ])
            ->add('title', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'style' => 'width: 100%; height: 75px;  resize: none;'],
                'label' => 'Başlık',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Başlık alanı boş olamaz.']),
                ],
            ])
            ->add('abstract', TextareaType::class, [
                'attr' => ['class' => 'form-control custom-textarea-class', 'style' => 'width: 100%; height: 250px; overflow-y: scroll;'],
                'label' => 'Özet',
                'required' => false
            ])
            ->add('keywords', ChoiceType::class, [
                'attr' => ['class' => 'form-control select2-multiple'],
                'label' => 'Anahtar Kelimeler',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Anahtar Kelimeler alanı boş olamaz.']),
                ],
            ]);
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                if (isset($data['keywords'])) {
                    $keywordsInput = $data['keywords'];

                    $form->add('keywords', ChoiceType::class, [
                        'attr' => ['class' => 'form-control select2-multiple'],
                        'label' => 'Anahtar Kelimeler',
                        'multiple' => true,
                        'expanded' => false,
                        'required' => false,
                        'choices' => $this->generateKeywordChoices($keywordsInput),
                    ]);
                }
            });

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $translation = $event->getData();

                if ($translation instanceof Translations && $keywords = $translation->getKeywords()) {
                    $form->add('keywords', ChoiceType::class, [
                        'attr' => ['class' => 'form-control select2-multiple'],
                        'label' => 'Anahtar Kelimeler',
                        'multiple' => true,
                        'expanded' => false,
                        'required' => false,
                        'choices' => $this->generateKeywordChoices($keywords),
                    ]);
                }
            });
    }


    private function generateKeywordChoices($keywords): array
    {
        if (is_array($keywords)) {
            return array_combine($keywords, $keywords);
        }

        if (is_string($keywords)) {
            $keywords = explode(',', $keywords);
        }

        $keywords = array_map('trim', $keywords);

        return array_combine($keywords, $keywords);
    }




    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Translations::class,
        ]);
    }
}