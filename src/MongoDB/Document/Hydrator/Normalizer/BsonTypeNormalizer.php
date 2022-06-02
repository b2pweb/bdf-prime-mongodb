<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator\Normalizer;

use Bdf\Serializer\Context\DenormalizationContext;
use Bdf\Serializer\Context\NormalizationContext;
use Bdf\Serializer\Normalizer\AutoRegisterInterface;
use Bdf\Serializer\Normalizer\NormalizerInterface;
use Bdf\Serializer\Normalizer\NormalizerLoaderInterface;
use Bdf\Serializer\Type\Type;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\Timestamp;
use MongoDB\BSON\Type as BsonType;
use MongoDB\BSON\UTCDateTime;

/**
 * Serializer normalizer for MongoDB BSON types
 * This normalizer will let unmodified bson values
 */
class BsonTypeNormalizer implements NormalizerInterface, AutoRegisterInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($data, NormalizationContext $context)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, Type $type, DenormalizationContext $context)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $className): bool
    {
        return is_subclass_of($className, BsonType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function registerTo(NormalizerLoaderInterface $loader): void
    {
        $loader->associate(ObjectId::class, $this);
        $loader->associate(Binary::class, $this);
        $loader->associate(Decimal128::class, $this);
        $loader->associate(Javascript::class, $this);
        $loader->associate(Timestamp::class, $this);
        $loader->associate(UTCDateTime::class, $this);
        $loader->associate(Regex::class, $this);
    }
}
