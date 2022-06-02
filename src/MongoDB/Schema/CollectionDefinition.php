<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\Schema\IndexSetInterface;

/**
 * Store collection options and indexes
 * This class is immutable
 */
final class CollectionDefinition
{
    private string $name;
    private IndexSetInterface $indexSet;
    private array $options;

    /**
     * @param string $name The collection name
     * @param IndexSetInterface $indexSet Declared indexes
     * @param array $options Options
     */
    public function __construct(string $name, IndexSetInterface $indexSet, array $options)
    {
        $this->name = $name;
        $this->indexSet = $indexSet;
        $this->options = $options;
    }

    /**
     * Get the collection name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get set of indexes
     *
     * @return IndexSetInterface
     */
    public function indexes(): IndexSetInterface
    {
        return $this->indexSet;
    }

    /**
     * Get the array of options
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * Get one option
     *
     * @param string $name The option name
     *
     * @return mixed
     */
    public function option(string $name)
    {
        return $this->options[$name] ?? null;
    }
}
