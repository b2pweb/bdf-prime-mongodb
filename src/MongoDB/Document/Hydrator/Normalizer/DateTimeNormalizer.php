<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator\Normalizer;

use Bdf\Serializer\Context\DenormalizationContext;
use Bdf\Serializer\Context\NormalizationContext;
use Bdf\Serializer\Normalizer\NormalizerInterface;
use Bdf\Serializer\Type\Type;
use DateTimeInterface;
use MongoDB\BSON\UTCDateTime;

/**
 * Normalizer for DateTime objects
 *
 * DateTime will be normalized to `UTCDateTime`
 * The denormalization is not used because bdf-serializer ignore object values
 *
 * @implements NormalizerInterface<DateTimeInterface|int|float|string>
 */
class DateTimeNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($data, NormalizationContext $context)
    {
        return new UTCDateTime($data);
    }

    /**
     * {@inheritdoc}
     *
     * @param UTCDateTime $data
     */
    public function denormalize($data, Type $type, DenormalizationContext $context)
    {
        return $data->toDateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $className): bool
    {
        return is_subclass_of($className, DateTimeInterface::class);
    }
}
