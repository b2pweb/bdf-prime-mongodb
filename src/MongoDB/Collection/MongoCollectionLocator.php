<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\ConnectionRegistryInterface;
use Bdf\Prime\MongoDB\Document\DocumentMapper;

/**
 *
 */
class MongoCollectionLocator
{
    /**
     * @var ConnectionRegistryInterface
     */
    private ConnectionRegistryInterface $connections;

    /**
     * @psalm-var class-string-map<D, MongoCollection<D>>
     * @var array<class-string, MongoCollection>
     */
    private array $collections = [];

    /**
     * @param ConnectionRegistryInterface $connections
     */
    public function __construct(ConnectionRegistryInterface $connections)
    {
        $this->connections = $connections;
    }

    /**
     * @param class-string<D> $type
     * @return MongoCollectionInterface<D>
     *
     * @template D as object
     *
     * @todo handle inheritance (iterate class hierarchy)
     * @todo collection by name ?
     * @todo mapper factory / locator
     */
    public function collection(string $type): MongoCollectionInterface
    {
        if (isset($this->collections[$type])) {
            return $this->collections[$type];
        }

        $mapperClass = $type . 'Mapper';

        if (!class_exists($mapperClass)) {
            foreach (class_parents($type) as $documentType) {
                $mapperClass = $documentType . 'Mapper';

                if (class_exists($mapperClass)) {
                    break;
                }
            }
        }

        /** @var DocumentMapper<D> $mapper */
        $mapper = new $mapperClass($type);
        $this->collections[$type] = $collection = new MongoCollection(
            $this->connections->getConnection($mapper->connection()),
            $mapper
        );

        return $collection;
    }

    // @todo refactor: mapper resolver
    public function collectionByMapper(string $mapperClass): MongoCollection
    {
        /** @var DocumentMapper $mapper */
        $mapper = new $mapperClass();

        return new MongoCollection(
            $this->connections->getConnection($mapper->connection()),
            $mapper
        );
    }
}
