<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
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
     * {@inheritdoc}
     */
    public function execute(MongoConnection $connection)
    {
        return new CursorResultSet($connection->runCommand($this));
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

    /**
     * Convert command to string representation
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->document());
    }
}
