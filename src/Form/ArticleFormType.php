<?php

namespace App\Form;

use App\Entity\Articles;
use App\Entity\Citations;
use App\Form\DataTransformer\CitationsToTextTransformer;
use App\Form\Type\PageRangeType;
use App\Params\ArticleLanguageParam;
use App\Params\ArticleTypeParam;
use App\Params\AuthorPartParam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ArticleFormType extends AbstractType
{
    private CitationsToTextTransformer $citationsTransformer;

    public function __construct(CitationsToTextTransformer $citationsTransformer)
    {
        $this->citationsTransformer = $citationsTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('primaryLanguage', ChoiceType::class,
                [
                    'label' => 'Birincil Dil*',
                    'choices' =>
                        ArticleLanguageParam::languages

                ])
            ->add('pageRange', PageRangeType::class, [
                'label' => 'Sayfa Aralığı*',
                'constraints' => [

                ],
            ])
            ->add('doi', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off',

                ],
                'label' => 'Doi',
                'required' => false,

            ])
            ->add('type', ChoiceType::class, [
                'choices' =>
                   ArticleTypeParam::choices,
                'label' => 'Makale Türü*'
            ])
            ->add('authors', CollectionType::class, [
                'entry_type' => AuthorFormType::class,
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
                'label' => ' ',
            ])
            ->add('translators', CollectionType::class, [
                'entry_type' => TranslatorFormType::class,
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
                'label' => ' ',
                'constraints' => [
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) use ($builder) {
                            $data = $builder->getData();
                            if ($data instanceof Articles && $data->getType() === ArticleTypeParam::TRANSLATE && count($value) === 0) {
                                $context->buildViolation('Çevirmen eklemelisiniz.')
                                    ->atPath('translators')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
            ])
            ->add('translations', CollectionType::class, [
                'entry_type' => TranslationsFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => ' ',

                'constraints' => [
                    new Count(['min' => 1, 'minMessage' => 'En Az 1 Tane Üst Veri Giriniz.']),
                ],
            ])
            ->add('citations', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'style' => 'width: 100%; height: 650px; overflow-y: scroll;'],
                'label' => 'Atıf',
                'required' => false,
//                'constraints' => [
//                    new NotBlank(['message' => 'Atıf Boş olamaz.']),
//                ],
            ])
            ->add('receivedDate', DateType::class, [
                'widget' => 'single_text',
                'placeholder' => [
                    'year' => 'Yıl', 'month' => 'Ay', 'day' => 'Gün',
                ],
//                'format' => 'YYYY-mm-dd',

                'label' => 'Geliş Tarihi',
                'required' => false,
                'attr' => ['class' => 'js-datepicker wide-input',
                    'autocomplete' => 'off',
],
                'html5' => false,

            ])
            ->add('acceptedDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Kabul Tarihi',
                'attr' => ['class' => 'js-datepicker wide-input',
                    'autocomplete' => 'off',
],
                'html5' => false,
                'required' => false,
            ]);
        $builder->get('citations')->addModelTransformer($this->citationsTransformer);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var Articles $data */
            $data = $event->getData();

//            $citationsArray = $data->getCitations();
//            foreach ($citationsArray as $citations) {
//
//                if (empty($citations->getReferance())) {
//                    $form->get('citations')->addError(new FormError('Atıf Boş olamaz'));
//                }
//            }

            $languageCount = 0;
            foreach ($data->getTranslations() as $translation) {
                if ($data->getPrimaryLanguage() == $translation->getLocale()) {
                    $languageCount++;
                }
            }
            if ($languageCount < 1) {
                $form->addError(new FormError('Birincil Dil ile Uyumlu Üstveri Dili Girmediniz.'));
            }
            if ($languageCount > 1) {
                $form->addError(new FormError('Birden Fazla Birincil Dil ile uyumlu Üstveri Dili Girdiniz.'));
            }

              $authors = $data->getAuthors();

            // Her bir yazar için kontrol edelim
            $rows = [];
            foreach ($authors as $index => $author) {
                $row = $author->getRow();


                if (in_array($row, $rows)) {
                    $authorField = $form->get('authors')->get($index);
                    $authorField->addError(new FormError('Her bir yazarın sıra değeri farklı olmalıdır.'));
                }


                $rows[] = $row;
            }

$translators = $data->getTranslators();
     // Her bir Çevirmen için kontrol edelim
            $rows = [];
            foreach ($translators as $translator) {
                $row = $translator->getRow();


                if (in_array($row, $rows)) {
                    $form->get('translators')->addError(new FormError('Her bir Çevirmen sıra değeri farklı olmalıdır.'));

                }
                $rows[] = $row;
            }
        });

    }

    public
    function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Articles::class,
        ]);
    }
}
