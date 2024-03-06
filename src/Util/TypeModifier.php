<?php

namespace App\Util;
class TypeModifier
{
    function mapArticleType($type)
    {
        $articleTypeMap = [
            '001' => 'Çeviri',
            '002' => 'Makale',
            '003' => 'Olgu Sunumu',
            '004' => 'Editor Mektup',
            '005' => 'Derleme'
        ];

        return isset($articleTypeMap[$type]) ? $articleTypeMap[$type] : 'Bilinmeyen';
    }


    function convertLanguageCode($code)
    {
        $langCodes = [
            'tr' => '001',
            'en' => '002',
            'ge' => '003',
            'es' => '004',
            'ar' => '005',
            'ru' => '006',
            'fa' => '007',
        ];

        return isset($langCodes[$code]) ? $langCodes[$code] : array_search($code, $langCodes);
    }
}

