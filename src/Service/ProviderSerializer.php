<?php

namespace App\Service;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ProviderSerializer
{
    private Serializer $serializer;

    public function __construct()
    {
        $extractor = new PropertyInfoExtractor([], [
            new PhpDocExtractor(),
            new ReflectionExtractor(),
        ]);

        $typeExtractor = new PropertyInfoExtractor(
            typeExtractors: [$extractor]
        );

        $this->serializer = new Serializer(
            normalizers: [
                new ObjectNormalizer(propertyTypeExtractor: $typeExtractor),
                new ArrayDenormalizer(),
            ],
            encoders: ['json' => new JsonEncoder()]
        );
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }
}