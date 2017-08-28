<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Types\PlatformTypeInterface;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\UTCDateTime;

/**
 * Date type
 * @link https://docs.mongodb.com/manual/reference/bson-types/#date
 */
class BsonDateType implements PlatformTypeInterface
{
    /**
     * @var string
     */
    private $name;


    /**
     * BsonArrayType constructor.
     *
     * @param string $name
     */
    public function __construct($name = self::DATETIME)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(PlatformInterface $platform, array $field)
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(PlatformInterface $platform, $value)
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
    public function toDatabase(PlatformInterface $platform, $value)
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
    public function name()
    {
        return $this->name;
    }
}
