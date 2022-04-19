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
use Bdf\Prime\Types\TypesRegistryInterface;
use MongoDB\BSON\ObjectId;

/**
 * Apply extra methods to mongo queries according to related collection
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
     * Collect documents by attribute
     *
     * @var array{attribute: string, combine: bool}|null
     */
    private ?array $byOptions = null;

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

    /**
     * Indexing entities by an attribute value
     * Use combine for multiple entities with same attribute value
     *
     * @param ReadCommandInterface<ConnectionInterface, D> $query
     * @param string $attribute
     * @param bool $combine
     *
     * @return ReadCommandInterface<ConnectionInterface, D>
     */
    public function by(ReadCommandInterface $query, string $attribute, bool $combine = false)
    {
        $this->byOptions = [
            'attribute' => $attribute,
            'combine'   => $combine,
        ];

        return $query;
    }

    /**
     * Post processor for hydrating entities
     *
     * @param ResultSetInterface<array<string, mixed>> $data
     *
     * @return D[]|array<string, list<D>>
     * @throws PrimeException
     */
    public function processDocuments(ResultSetInterface $data): array
    {
        $mapper = $this->mapper;
        $types = $this->collection->connection()->platform()->types();
        $rows = $data->asRawArray();
        $byOptions = $this->byOptions;

        if (!$byOptions) {
            return $this->processAsList($rows, $mapper, $types);
        }

        if (!$byOptions['combine']) {
            return $this->processAsAssociative($rows, $mapper, $types, $byOptions['attribute']);
        }

        return $this->processAsGrouping($rows, $mapper, $types, $byOptions['attribute']);
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
        $ext = clone $this;

        $query->setExtension($ext);
        $query->post([$ext, 'processDocuments'], false);
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

    /**
     * @param ResultSetInterface $rows
     * @param DocumentMapperInterface<D> $mapper
     * @param TypesRegistryInterface $types
     *
     * @return list<D>
     */
    private function processAsList(ResultSetInterface $rows, DocumentMapperInterface $mapper, TypesRegistryInterface $types)
    {
        $documents = [];

        foreach ($rows as $doc) {
            $documents[] = $mapper->fromDatabase($doc, $types);
        }

        return $documents;
    }

    /**
     * @param ResultSetInterface $rows
     * @param DocumentMapperInterface<D> $mapper
     * @param TypesRegistryInterface $types
     * @param string $keyField
     *
     * @return array<array-key, D>
     */
    private function processAsAssociative(ResultSetInterface $rows, DocumentMapperInterface $mapper, TypesRegistryInterface $types, string $keyField)
    {
        $documents = [];

        foreach ($rows as $doc) {
            $key = $doc[$keyField] ?? '';
            $documents[$key] = $mapper->fromDatabase($doc, $types);
        }

        return $documents;
    }

    /**
     * @param ResultSetInterface $rows
     * @param DocumentMapperInterface<D> $mapper
     * @param TypesRegistryInterface $types
     * @param string $keyField
     *
     * @return array<array-key, list<D>>
     */
    private function processAsGrouping(ResultSetInterface $rows, DocumentMapperInterface $mapper, TypesRegistryInterface $types, string $keyField)
    {
        $documents = [];

        foreach ($rows as $doc) {
            $key = $doc[$keyField] ?? '';
            $documents[$key][] = $mapper->fromDatabase($doc, $types);
        }

        return $documents;
    }
}
