<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use MongoDB\Driver\Command;

/**
 * Mongo command utils
 */
final class Commands
{
    /**
     * Create a command depends of parameters
     *
     * If $commands is a string : create a SimpleCommand with $argument as argument
     * If $command is an array : create an ArrayCommand
     * If $command is a Command : wrap with DriverCommand
     * If $command is a CommandInterface : do nothing
     *
     * @param mixed $command
     * @param mixed $argument
     *
     * @return CommandInterface
     */
    public static function create($command, $argument = 1)
    {
        if ($command instanceof CommandInterface) {
            return $command;
        }

        if ($command instanceof Command) {
            return new DriverCommand($command);
        }

        if (is_array($command)) {
            return new ArrayCommand($command);
        }

        if (is_string($command)) {
            return new SimpleCommand($command, $argument);
        }

        throw new \InvalidArgumentException('Invalid command type '.gettype($command));
    }
}
