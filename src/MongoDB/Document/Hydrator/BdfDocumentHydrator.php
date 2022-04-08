<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator;

use Bdf\Prime\MongoDB\Document\Hydrator\Normalizer\BsonTypeNormalizer;
use Bdf\Prime\MongoDB\Document\Hydrator\Normalizer\DateTimeNormalizer;
use Bdf\Serializer\Metadata\Driver\AnnotationsDriver;
use Bdf\Serializer\Metadata\Driver\StaticMethodDriver;
use Bdf\Serializer\Metadata\MetadataFactory;
use Bdf\Serializer\Normalizer\NormalizerLoader;
use Bdf\Serializer\Normalizer\ObjectNormalizer;
use Bdf\Serializer\Normalizer\PropertyNormalizer;
use Bdf\Serializer\Normalizer\TraversableNormalizer;
use Bdf\Serializer\Serializer;
use Bdf\Serializer\SerializerInterface;

/**
 * Hydrator using bdf-serializer
 */
final class BdfDocumentHydrator implements DocumentHydratorInterface
{
    private SerializerInterface $serializer;

    /**
     * @param SerializerInterface|null $serializer
     */
    public function __construct(?SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer ?? self::createDefaultSerializer();
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(object $document, array $data): object
    {
        $this->serializer->fromArray($data, $document);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(object $document): array
    {
        return $this->serializer->toArray($document);
    }

    private static function createDefaultSerializer(): SerializerInterface
    {
        $loader = new NormalizerLoader([
            new BsonTypeNormalizer(),
            new DateTimeNormalizer(),
            new TraversableNormalizer(),
            new ObjectNormalizer(),
            new PropertyNormalizer(new MetadataFactory([
                new StaticMethodDriver(),
                new AnnotationsDriver(),
            ])) // @todo cache ?
        ]);

        return new Serializer($loader);
    }
}
