<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Types\PlatformTypeInterface;

/**
 * Integer type
 */
class BsonIntegerType implements PlatformTypeInterface
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
    public function __construct($name = self::INTEGER)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(PlatformInterface $platform, array $field)
    {
        return 'int';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(PlatformInterface $platform, $value)
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(PlatformInterface $platform, $value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->name;
    }
}
