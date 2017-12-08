<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use MongoDB\Driver\Command;

/**
 * Base implementation of command
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return [
            $this->name() => $this->argument()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return new Command($this->document());
    }

    /**
     * Get the base argument of the command
     *
     * @return mixed
     */
    protected function argument()
    {
        return 1;
    }
}
