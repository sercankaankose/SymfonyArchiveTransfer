<?php

namespace App\Form\DataTransformer;

use App\Entity\Citations;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

class CitationsToTextTransformer implements DataTransformerInterface
{

    public function transform($citations)
    {
        if ($citations !== null) {
            return implode("\r\n\r\n", $citations->map(function ($citation) {
                return $citation->getReferance();
            })->toArray());
        }

        return '';
    }

    public function reverseTransform($formattedCitations)
    {
        $citationsArray = explode("\r\n\r\n", $formattedCitations);
        $citationsCollection = new ArrayCollection();

        foreach ($citationsArray as $citationText) {
            $citation = $this->createCitationFromText($citationText);
            $citationsCollection->add($citation);
        }

        return $citationsCollection;
    }

    private function createCitationFromText($text)
    {
        $citation = new Citations();
        $citation->setReferance($text);
        return $citation;
    }
}
