<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\MongoDB\Query\Command\Create;
use Bdf\Prime\Schema\TableInterface;
use MongoDB\Driver\Command;

/**
 * Generate commands for schema creation on MongoDB
 */
class SchemaCreation implements CommandSetInterface
{
    /**
     * @var TableInterface[]
     */
    private $tables;


    /**
     * SchemaCreation constructor.
     *
     * @param TableInterface[] $tables
     */
    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     * @return TableInterface[]
     */
    public function tables()
    {
        return $this->tables;
    }

    /**
     * {@inheritdoc}
     */
    public function commands()
    {
        $commands = [];

        foreach ($this->tables as $table) {
            $commands[] = $this->collectionCreationCommand($table);

            $commands = array_merge(
                $commands,
                (new IndexSetDiff(
                    $table->name(),
                    new IndexSetCreationComparator($table->indexes())
                ))->commands()
            );
        }

        return $commands;
    }

    private function collectionCreationCommand(TableInterface $table): Create
    {
        $command = new Create($table->name());

        foreach ($table->options() as $option => $value) {
            $command->$option($value);
        }

        return $command;
    }
}
