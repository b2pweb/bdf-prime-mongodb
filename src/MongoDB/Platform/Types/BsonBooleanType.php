<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Types\PlatformTypeInterface;

/**
 * Boolean type
 */
class BsonBooleanType implements PlatformTypeInterface
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
    public function __construct($name = self::BOOLEAN)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(PlatformInterface $platform, array $field)
    {
        return 'bool';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(PlatformInterface $platform, $value)
    {
        return $value === null ? null : (bool) $value;
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
