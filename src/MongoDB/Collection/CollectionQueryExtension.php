<?php

namespace Bdf\Prime\MongoDB\Collection;

use BadMethodCallException;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\ReadCommandInterface;
use MongoDB\BSON\ObjectId;

/**
 * Class CollectionQueryExtension
 *
 * @template D as object
 */
class CollectionQueryExtension
{
    /**
     * @var MongoCollectionInterface<D>
     */
    private MongoCollectionInterface $collection;

    /**
     * @var DocumentMapperInterface<D>
     */
    private DocumentMapperInterface $mapper;

    /**
     * @param MongoCollectionInterface<D> $collection
     * @param DocumentMapperInterface<D> $mapper
     */
    public function __construct(MongoCollectionInterface $collection, DocumentMapperInterface $mapper)
    {
        $this->collection = $collection;
        $this->mapper = $mapper;
    }

    /**
     * Get one entity by identifier
     *
     * @param ReadCommandInterface<ConnectionInterface, D>&Whereable $query
     * @param ?ObjectId $id
     * @param null|string|array $attributes
     *
     * @return D|null
     */
    public function get(ReadCommandInterface $query, ?ObjectId $id, $attributes = null)
    {
        if (!$id) {
            return null;
        }

        /** @psalm-suppress InvalidArgument */
        return $query->whereRaw(['_id' => $id])->first($attributes);
    }

    /**
     * Get one entity or throws entity not found
     *
     * @param ReadCommandInterface<ConnectionInterface, D>&Whereable $query
     * @param ?ObjectId $id
     * @param null|string|array $attributes
     *
     * @return D
     *
     * @throws EntityNotFoundException  If entity is not found
     */
    public function getOrFail(ReadCommandInterface $query, ?ObjectId $id, $attributes = null)
    {
        $entity = $this->get($query, $id, $attributes);

        if ($entity !== null) {
            return $entity;
        }

        throw new EntityNotFoundException('Cannot resolve entity identifier "' . $id . '"');
    }

//    /**
//     * Get one entity or return a new one if not found in repository
//     *
//     * @param ReadCommandInterface<ConnectionInterface, D>&Whereable $query
//     * @param ?ObjectId $id
//     * @param null|string|array $attributes
//     *
//     * @return D
//     */
//    public function getOrNew(ReadCommandInterface $query, ?ObjectId $id, $attributes = null)
//    {
//        $entity = $this->get($query, $id, $attributes);
//
//        if ($entity !== null) {
//            return $entity;
//        }
//
//        return $this->repository->entity();
//    }

    /**
     * Post processor for hydrating entities
     *
     * @param ResultSetInterface<array<string, mixed>> $data
     *
     * @return list<D>
     * @throws PrimeException
     */
    public function processDocuments(ResultSetInterface $data): array
    {
        $documents = [];

        $mapper = $this->mapper;
        $types = $this->collection->connection()->platform()->types();

        foreach ($data->asRawArray() as $doc) {
            $documents[] = $mapper->fromDatabase($doc, $types);
        }

        return $documents;
    }

    /**
     * Configure the query
     *
     * @param ReadCommandInterface $query
     *
     * @return void
     */
    public function apply(ReadCommandInterface $query): void
    {
        $query->setExtension($this);
        $query->post([$this, 'processDocuments'], false);
    }

    /**
     * Scope call
     * run a scope defined on mapper
     *
     * @param string $name Scope name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $scopes = $this->mapper->scopes();

        if (!isset($scopes[$name])) {
            throw new BadMethodCallException('Scope "' . get_class($this->mapper) . '::' . $name . '" not found');
        }

        return $scopes[$name](...$arguments);
    }
}
