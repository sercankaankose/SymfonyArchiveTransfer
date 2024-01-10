<?php

namespace App\Form\Type;

use App\Form\DataTransformer\PageRangeTransformer;
use PhpParser\Node\Scalar\String_;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PageRangeType extends AbstractType
{
    private PageRangeTransformer $transformer;

    public function __construct(PageRangeTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer($this->transformer);
    }

    public function getParent(): ?String
    {
        return TextType::class;
    }
}
