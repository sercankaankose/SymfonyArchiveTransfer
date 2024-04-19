<?php

namespace App\Util;
use App\Form\ArticleFormType;
use App\Params\ArticleLanguageParam;
use App\Params\ArticleTypeParam;

class TypeModifier
{
    function mapArticleType($type)
    {
        $articleTypeMap =
           ArticleTypeParam::choices
        ;

        return isset($articleTypeMap[$type]) ? $articleTypeMap[$type] : 'Bilinmeyen';
    }


    function convertLanguageCode($code)
    {
        $langCodes = ArticleLanguageParam::languages;

        return isset($langCodes[$code]) ? $langCodes[$code] : array_search($code, $langCodes);
    }
}

