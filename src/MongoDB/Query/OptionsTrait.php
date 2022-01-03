<?php

namespace Bdf\Prime\MongoDB\Query;

/**
 * Implements @see OptionsConfigurable
 *
 * @psalm-require-implements OptionsConfigurable
 */
trait OptionsTrait
{
    /**
     * {@inheritdoc}
     */
    public function option(string $name, $value)
    {
        $this->statements['options'][$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function collation(array $collation)
    {
        return $this->option('collation', $collation);
    }

    /**
     * {@inheritdoc}
     */
    public function hint($hint)
    {
        return $this->option('hint', $hint);
    }

    /**
     * {@inheritdoc}
     */
    public function multi(bool $flag = true)
    {
        return $this->option('multi', $flag);
    }

    /**
     * {@inheritdoc}
     */
    public function upsert(bool $flag = true)
    {
        return $this->option('upsert', $flag);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayFilters(array $filters)
    {
        return $this->option('arrayFilters', $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function onlyDeleteFirst(bool $flag = true)
    {
        return $this->option('limit', $flag);
    }
}
