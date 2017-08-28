<?php

namespace Bdf\Prime\MongoDB\Schema;

use MongoDB\Driver\Command;

/**
 * Interface for represent objects which can generate set of commands
 */
interface CommandSetInterface
{
    /**
     * Get the commands
     *
     * @return Command[]
     */
    public function commands();
}
