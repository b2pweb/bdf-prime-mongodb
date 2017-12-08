<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use MongoDB\Driver\Command;

/**
 * Base interface for Prime mongo commands
 */
interface CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    public function name();

    /**
     * Get the command document
     *
     * @return array
     *
     * @see CommandInterface::get() For get the realm command object
     */
    public function document();

    /**
     * Get the mongodb command object
     * This method is equivalent to `return new Command($command->document());`
     *
     * @return Command
     *
     * @see CommandInterface::document() For get the command payload
     */
    public function get();
}
