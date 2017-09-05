<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use MongoDB\BSON\Binary;

/**
 * BinData type
 */
class BsonBinDataType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::BLOB)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(array $field)
    {
        return 'binData';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Binary) {
            return $value->getData();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        return new Binary($value, Binary::TYPE_GENERIC);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PhpTypeInterface::STRING;
    }
}
