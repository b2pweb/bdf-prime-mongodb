<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;

/**
 * Object type
 */
class BsonObjectType extends AbstractPlatformType
{
    /**
     * Map the prime type that should be map as array
     *
     * @var array
     */
    private $arrayTypeMap = [
        self::ARRAY_OBJECT => true,
        self::JSON => true,
    ];

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
    public function declaration(ColumnInterface $column)
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

        if (isset($this->arrayTypeMap[$this->name])) {
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
        return isset($this->arrayTypeMap[$this->name])
            ? PhpTypeInterface::TARRAY
            : PhpTypeInterface::OBJECT;
    }
}
