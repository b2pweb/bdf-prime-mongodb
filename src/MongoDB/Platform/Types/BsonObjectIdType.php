<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use MongoDB\BSON\ObjectId;

/**
 * ObjectId type
 * @link https://docs.mongodb.com/manual/reference/bson-types/#objectid
 */
class BsonObjectIdType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::GUID)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return 'objectId';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value === null) {
            return null;
        }

        return ltrim($value, '0');
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ObjectId) {
            return $value;
        }

        $len = strlen($value);

        if ($len < 24) {
            $value = str_repeat('0', 24 - $len) . $value;
        }

        return new ObjectId($value);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PhpTypeInterface::STRING;
    }
}
