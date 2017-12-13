<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\MongoDB\Query\Command\CommandInterface;

/**
 * Interface for represent objects which can generate set of commands
 */
interface CommandSetInterface
{
    /**
     * Get the commands
     *
     * @return CommandInterface[]
     */
    public function commands();
}
