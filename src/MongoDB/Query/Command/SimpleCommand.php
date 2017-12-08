<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * Simple mongo command
 *
 * Will result of a document in form : {"name": $argument}
 */
class SimpleCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $argument;


    /**
     * SimpleCommand constructor.
     *
     * @param string $name The command name
     * @param mixed $argument The command argument
     */
    public function __construct($name, $argument = 1)
    {
        $this->name = $name;
        $this->argument = $argument;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    protected function argument()
    {
        return $this->argument;
    }
}
