<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PageRangeTransformer implements DataTransformerInterface
{

    /** Veritabanından forma dönüştürme */

    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        return $value['first_page'] . '-' . $value['last_page'];
    }

    /** Formdan veritabanından dönüştürme */

    public function reverseTransform($value)
    {
        $pages = explode('-', $value);

        if (count($pages) !== 2 || !is_numeric($pages[0]) || !is_numeric($pages[1])) {
            throw new TransformationFailedException('Geçersiz sayfa aralığı');
        }

// veritabanına uygun formatında dönüştürme
        return [
            'first_page' => (int)$pages[0],
            'last_page' => (int)$pages[1],
        ];
    }
}
