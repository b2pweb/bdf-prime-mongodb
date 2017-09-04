<?php

namespace Bdf\Prime\MongoDB\Schema;

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
            $commands[] = new Command([
                "create" => $table->name()
            ]);

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
}
