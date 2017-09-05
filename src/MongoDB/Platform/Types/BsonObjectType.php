<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Types\PhpTypeInterface;

/**
 * Object type
 */
class BsonObjectType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::OBJECT)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(array $field)
    {
        return 'object';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        if ($this->name === self::JSON) {
            return (array) $value;
        }

        return (object) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        return $value === null ? null : (object) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return $this->name === self::JSON
            ? PhpTypeInterface::TARRAY
            : PhpTypeInterface::OBJECT;
    }
}
