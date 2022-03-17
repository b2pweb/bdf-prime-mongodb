<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;

/**
 * Double type
 */
class BsonDoubleType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::DOUBLE)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return 'double';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        return $value === null ? null : (double) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        return $value === null ? null : (double) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::DOUBLE;
    }
}
