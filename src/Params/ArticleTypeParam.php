<?php


namespace App\Params;

use App\Entity\User;
use Symfony\Component\Security\Http\Authenticator\AccessTokenAuthenticator;

class ArticleTypeParam{
    const TRANSLATE = '57';
    const ARTICLE = '2';

const choices = [
    'Görüntü Sunumu' => '1',
    'Teknik Not' => '2',
    'Tez Özeti' => '3',
    'Photo-Quiz' => '4',
    'Klinik Araştırma' => '5',
    'Monografi' => '6',
    'Kısa Rapor' => '7',
    'Sistematik Derlemeler ve Meta Analiz' => '8',
    'Söyleşi' => '9',
    'İnceleme Makalesi' => '10',
    'Tartışma' => '11',
    'Otobiyografi' => '23',
    'Düşünce Yazısı' => '24',
    'Öğretim Uygulaması' => '25',
    'Teorik Makale' => '26',
    'Görüş Makalesi' => '27',
    'Mektup' => '29',
    'Sayıdan Geri Çekildi' => '30',
    'Araştırma Notu' => '31',
    'Vefeyât' => '32',
    'Oyun İncelemesi' => '33',
    'Protokol Makalesi' => '34',
    'Araştırma Makalesi' => '54',
    'Olgu Sunumu' => '55',
    'Derleme' => '56',
    'Çeviri' => '57',
    'Konferans Bildirisi' => '58',
    'Kısa Bildiri' => '59',
    'Editöre Mektup' => '60',
    'Not' => '61',
    'Toplantı Özeti' => '62',
    'Yasa İncelemesi' => '63',
    'Karar İncelemesi' => '64',
    'Kitap İncelemesi' => '65',
    'Düzeltme' => '66',
    'Editoryal' => '67',
    'Biyografi' => '68',
    'Bibliyografi' => '69',
    'Haber' => '70',
    'Rapor' => '71',
    'Sanat ve Edebiyat' => '72',
    'Diğer' => '73'
];

}