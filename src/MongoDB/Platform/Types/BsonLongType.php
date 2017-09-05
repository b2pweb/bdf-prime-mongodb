<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Types\PhpTypeInterface;

/**
 * Long type
 */
class BsonLongType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::BIGINT)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(array $field)
    {
        return 'long';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        if (PHP_INT_SIZE < 8) {
            return (string) $value;
        } else {
            return (int) $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PHP_INT_SIZE < 8
            ? PhpTypeInterface::STRING
            : PhpTypeInterface::INTEGER;
    }
}
