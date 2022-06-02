<?php

namespace Bdf\Prime\MongoDB\Document\Mapping;

use Bdf\Prime\Types\TypesRegistryInterface;

/**
 * Class Field
 *
 * @todo default value
 */
class Field
{
    private string $name;
    private string $type;
    private ?string $phpType;

    /**
     * @param string $name
     * @param string $type
     * @param string|null $phpType
     *
     * @todo type options
     */
    public function __construct(string $name, string $type, ?string $phpType)
    {
        $this->name = $name;
        $this->type = $type;
        $this->phpType = $phpType;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    public function fromDatabase($value, TypesRegistryInterface $types)
    {
        return $types->get($this->type)->fromDatabase($value, ['phpType' => $this->phpType]);
    }

    public function toDatabase($value, TypesRegistryInterface $types)
    {
        return $types->get($this->type)->toDatabase($value);
    }
}
