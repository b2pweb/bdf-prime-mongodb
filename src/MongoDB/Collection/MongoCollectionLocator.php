<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\ConnectionRegistryInterface;
use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\MongoDB\Document\Factory\DocumentMapperFactory;
use Bdf\Prime\MongoDB\Document\Factory\DocumentMapperFactoryInterface;

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
     * @var DocumentMapperFactoryInterface
     */
    private DocumentMapperFactoryInterface $mapperFactory;

    /**
     * @psalm-var class-string-map<D, MongoCollection<D>>
     * @var array<class-string, MongoCollection>
     */
    private array $collections = [];

    /**
     * @param ConnectionRegistryInterface $connections
     * @param DocumentMapperFactoryInterface|null $mapperFactory Mapper factory. By default, use `DocumentMapperFactory`
     */
    public function __construct(ConnectionRegistryInterface $connections, ?DocumentMapperFactoryInterface $mapperFactory = null)
    {
        $this->connections = $connections;
        $this->mapperFactory = $mapperFactory ?? new DocumentMapperFactory();
    }

    /**
     * Get the collection related to the given document class
     *
     * @param class-string<D> $type Document class name
     * @return MongoCollectionInterface<D>
     *
     * @template D as object
     */
    public function collection(string $type): MongoCollectionInterface
    {
        if (isset($this->collections[$type])) {
            return $this->collections[$type];
        }

        $mapper = $this->mapperFactory->createByDocumentClassName($type);
        $connection = $this->connections->getConnection($mapper->connection());

        $this->collections[$type] = $collection = $mapper->createMongoCollection($connection);

        return $collection;
    }

    /**
     * Create a collection by a mapper class name
     *
     * @param class-string<DocumentMapperInterface> $mapperClass Mapper to create
     * @param class-string<D>|null $documentClass Document class to use. If null, use the default related document class
     *
     * @return MongoCollectionInterface<D>
     *
     * @template D as object
     */
    public function collectionByMapper(string $mapperClass, ?string $documentClass = null): MongoCollectionInterface
    {
        /** @var DocumentMapperInterface<D> $mapper Psalm can't handle |null with template */
        $mapper = $this->mapperFactory->createByMapperClassName($mapperClass, $documentClass);
        $connection = $this->connections->getConnection($mapper->connection());

        return $mapper->createMongoCollection($connection);
    }
}
