<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use DateTimeZone;
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
    public function declaration(ColumnInterface $column)
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value === null || !$value instanceof UTCDateTime) {
            return null;
        }

        $timezone = $fieldOptions['timezone'] ?? date_default_timezone_get();

        return $value->toDateTime()->setTimezone(new DateTimeZone($timezone));
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
        $ts += (int) $value->format('u') / 1000;

        return new UTCDateTime($ts);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::DATETIME;
    }
}
