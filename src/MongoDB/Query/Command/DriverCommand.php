<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use MongoDB\Driver\Command;

/**
 * Wrap the driver Command to CommandInterface
 *
 * /!\ Because Command is not readable, document and name cannot be extracted
 */
class DriverCommand implements CommandInterface
{
    /**
     * @var Command
     */
    private $command;


    /**
     * DriverCommand constructor.
     *
     * @param Command $command
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(MongoConnection $connection)
    {
        return new CursorResultSet($connection->runCommand($this));
    }
}
