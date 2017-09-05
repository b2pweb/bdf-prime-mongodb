<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use MongoDB\BSON\UTCDateTime;

/**
 * Date type
 * @link https://docs.mongodb.com/manual/reference/bson-types/#date
 */
class BsonDateType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::DATETIME)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(array $field)
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        if ($value === null || !$value instanceof UTCDateTime) {
            return null;
        }

        return $value->toDateTime();
    }

    /**
     * {@inheritdoc}
     *
     * @param \DateTimeInterface $value
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        $ts = $value->getTimestamp() * 1000;
        $ts += (int) $value->format('u');

        return new UTCDateTime($ts);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PhpTypeInterface::DATETIME;
    }
}
