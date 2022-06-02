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
     * @var list<TableInterface|CollectionDefinition>
     */
    private array $collections;


    /**
     * SchemaCreation constructor.
     *
     * @param list<TableInterface|CollectionDefinition> $collections
     */
    public function __construct(array $collections)
    {
        $this->collections = $collections;
    }

    /**
     * @return list<TableInterface|CollectionDefinition>
     * @deprecated Use collections() instead
     */
    public function tables()
    {
        return $this->collections;
    }

    /**
     * @return list<TableInterface|CollectionDefinition>
     */
    public function collections()
    {
        return $this->collections;
    }

    /**
     * {@inheritdoc}
     */
    public function commands()
    {
        $commands = [];

        foreach ($this->collections as $collection) {
            $commands[] = $this->collectionCreationCommand($collection);

            $commands = array_merge(
                $commands,
                (new IndexSetDiff(
                    $collection->name(),
                    new IndexSetCreationComparator($collection->indexes())
                ))->commands()
            );
        }

        return $commands;
    }

    /**
     * @param TableInterface|CollectionDefinition $collection
     * @return Create
     */
    private function collectionCreationCommand($collection): Create
    {
        return new Create($collection->name(), $collection->options());
    }
}
