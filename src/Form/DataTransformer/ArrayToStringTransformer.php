<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ArrayToStringTransformer implements DataTransformerInterface
{
    public function transform($value): string
    {

        /**
         * Veritabanından forma dönüştürme
         */
        if (!is_array($value)) {
            return '';
        }

        return implode(', ', $value);
    }


    public function reverseTransform($value): array
    {
        /**
         * Formdan veritabanına dönüştürme
         */
        return explode(', ', $value);
    }

    //
    //
    //namespace App\Form\DataTransformer;
    //
    //use Symfony\Component\Form\DataTransformerInterface;
    //
    //class ArrayToStringTransformer implements DataTransformerInterface
    //{
    //    public function transform($value): string
    //    {
    //        /**
    //         * Veritabanından forma dönüştürme
    //         */
    //        if (!is_array($value)) {
    //            return '';
    //        }
    //
    //        return implode(', ', $value);
    //    }
    //
    //    public function reverseTransform($value): array
    //    {
    //        /**
    //         * Formdan veritabanına dönüştürme
    //         */
    //        $keywords = explode(', ', $value);
    //
    //        // Select2 tarafından otomatik olarak eklenen yeni etiketleri temizle
    //        foreach ($keywords as $key => $keyword) {
    //            if (strpos($keyword, '__') === 0) {
    //                unset($keywords[$key]);
    //            }
    //        }
    //
    //        return array_values($keywords);
    //    }
    //}
}
