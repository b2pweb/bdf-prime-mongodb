<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\MongoDB\Collection\MongoCollectionInterface;
use Bdf\Prime\MongoDB\Driver\Exception\MongoCommandException;
use Bdf\Prime\Schema\StructureUpgraderInterface;
use MongoDB\Driver\Exception\CommandException;

/**
 * Upgrader for mongodb collection structure
 */
class CollectionStructureUpgrader implements StructureUpgraderInterface
{
    private MongoCollectionInterface $collection;

    /**
     * @param MongoCollectionInterface $collection
     */
    public function __construct(MongoCollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(bool $listDrop = true): void
    {
        $this->schema()->add($this->collection->mapper()->definition());
    }

    /**
     * {@inheritdoc}
     */
    public function diff(bool $listDrop = true): array
    {
        return $this->schema()
            ->simulate(function (MongoSchemaManager $schema) {
                $schema->add($this->collection->mapper()->definition());
            })
            ->pending()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function truncate(bool $cascade = false): bool
    {
        $this->schema()->truncate($this->collection->mapper()->collection());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function drop(): bool
    {
        try {
            $this->schema()->drop($this->collection->mapper()->collection());
        } catch (MongoCommandException $e) {
            if ($e->errorCode() === 'NamespaceNotFound') {
                return false;
            }

            throw $e;
        }

        return true;
    }

    /**
     * @return MongoSchemaManager
     */
    private function schema(): MongoSchemaManager
    {
        return $this->collection->connection()->schema();
    }
}
