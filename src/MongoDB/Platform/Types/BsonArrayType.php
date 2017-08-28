<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Types\PlatformTypeInterface;

/**
 * Array type
 */
class BsonArrayType implements PlatformTypeInterface
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
    public function __construct($name = self::TARRAY)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(PlatformInterface $platform, array $field)
    {
        return 'array';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(PlatformInterface $platform, $value)
    {
        return $value === null ? null : (array) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(PlatformInterface $platform, $value)
    {
        return (array) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->name;
    }
}
