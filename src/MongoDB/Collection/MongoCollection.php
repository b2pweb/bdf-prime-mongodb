<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Util\Arr;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;

/**
 * Store and access to mongo documents
 *
 * @template D as object
 * @mixin MongoQuery<D>
 *
 * @todo interface, parce que c'est bien
 * @todo gestion des exceptions
 *
 * @todo lazy connection
 */
class MongoCollection
{
    private MongoConnection $connection;

    /**
     * @var DocumentMapperInterface<D>
     */
    private DocumentMapperInterface $mapper;

    /**
     * @param MongoConnection $connection
     * @param DocumentMapperInterface<D> $mapper
     */
    public function __construct(MongoConnection $connection, DocumentMapperInterface $mapper)
    {
        $this->connection = $connection;
        $this->mapper = $mapper;
    }

    /**
     * Add the document to the collection
     * If the id is not provided, it will be generated
     * If the document already exists, this method will fail
     *
     * @param D $document
     * @return void
     */
    public function add(object $document): void
    {
        $data = $this->mapper->toDatabase($document, $this->connection->platform()->types());

        if (empty($data['_id'])) {
            $data['_id'] = new ObjectId();
            $this->mapper->setId($document, $data['_id']);
        }

        $write = new WriteQuery($this->mapper->collection());
        $write->insert($data);

        $write->execute($this->connection);
    }

    /**
     * Replace the document on the collection
     * If the id is not provided, it will be generated, and perform a simple insert
     * If the document already exist, this method will replace the document
     * If the document do not exist, it will be inserted
     *
     * @param D $document
     * @return void
     */
    public function replace(object $document): void
    {
        if ($this->mapper->getId($document) === null) {
            $this->add($document);
            return;
        }

        $data = $this->mapper->toDatabase($document, $this->connection->platform()->types());

        $write = new WriteQuery($this->mapper->collection());
        $write->update(['_id' => $data['_id']], $data, ['upsert' => true]);

        $write->execute($this->connection);
    }

    /**
     * Perform a simple update of the document
     *
     * @param D $document Document to update
     * @param list<string> $fields List of fields to update. For embedded fields, use "dot" notation (i.e. "embedded.subField"). If empty, all fields will be updated.
     *
     * @return void
     */
    public function update(object $document, array $fields = []): void
    {
        if (($id = $this->mapper->getId($document)) === null) {
            throw new InvalidArgumentException('The document id is missing');
        }

        // @todo optimise ? provide fields
        $data = $this->mapper->toDatabase($document, $this->connection->platform()->types());

        if ($fields) {
            $changes = [];

            foreach ($fields as $field) {
                $changes[$field] = Arr::get($data, $field);
            }
        } else {
            $changes = $data;
        }

        $write = new WriteQuery($this->mapper->collection());
        $write->update(['_id' => $id], ['$set' => $changes]);

        $write->execute($this->connection);
    }

    /**
     * Delete the document from the collection
     *
     * If the document has no id, or do not exist, this will do nothing
     *
     * @param D $document
     * @return void
     */
    public function delete(object $document): void
    {
        $id = $this->mapper->getId($document);

        if ($id === null) {
            return;
        }

        $write = new WriteQuery($this->mapper->collection());
        $write->delete(['_id' => $id]);

        $write->execute($this->connection);
    }

    /**
     * Get a document by its id
     *
     * @param ObjectId $id
     *
     * @return D|null The document, or null if not exists
     */
    public function get(ObjectId $id): ?object
    {
        return $this->findOneRaw(['_id' => $id]);
    }

    /**
     * Check the existence of the document in the collection
     * This method will only check the "_id" field.
     *
     * @param D $document Document to check
     * @return bool true if exists
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function exists(object $document): bool
    {
        $id = $this->mapper->getId($document);

        if (!$id) {
            return false;
        }

        $count = new Count($this->mapper->collection());
        $count->query(['_id' => $id] + $this->mapper->constraints());

        foreach ($count->execute($this->connection) as $row) {
            return $row['n'];
        }

        return 0;
    }

    /**
     * Reload the document and get fields from database
     *
     * The document passed as parameter will be unmodified
     * This method is equivalent to call `$collection->get($document->id());`
     *
     * @param D $document Document to refresh
     *
     * @return D|null The document, or null if not exists
     */
    public function refresh(object $document): ?object
    {
        $id = $this->mapper->getId($document);

        return $id ? $this->get($id) : null;
    }

    /**
     * Perform a search on the collection
     *
     * @param array $filters Raw mongodb filters
     * @param array $options Search options
     *
     * @return D[]
     */
    public function findAllRaw(array $filters = [], array $options = []): iterable
    {
        $filters += $this->mapper->constraints();
        $query = new ReadQuery($this->mapper->collection(), $filters, $options);
        $result = $query->execute($this->connection);

        foreach ($result->asRawArray() as $key => $document) {
            yield $key => $this->mapper->fromDatabase($document, $this->connection->platform()->types());
        }
    }

    /**
     * Perform a search on the collection, and return a single entity
     *
     * @param array $filters Raw mongodb filters
     * @param array $options Search options
     *
     * @return D|null The document if exists, or null
     */
    public function findOneRaw(array $filters = [], array $options = []): ?object
    {
        $filters += $this->mapper->constraints();
        $query = new ReadQuery($this->mapper->collection(), $filters, $options + ['limit' => 1]);
        $result = $query->execute($this->connection);

        foreach ($result->asRawArray() as $document) {
            return $this->mapper->fromDatabase($document, $this->connection->platform()->types());
        }

        return null;
    }

    /**
     * Count matching documents on the collection
     *
     * @param array $filters Criteria
     * @return int
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function count(array $filters = []): int
    {
        return $this->query()->where($filters)->count();
    }

    /**
     * @return MongoConnection
     */
    public function connection(): MongoConnection
    {
        return $this->connection;
    }

    public function mapper(): DocumentMapperInterface
    {
        return $this->mapper;
    }

    public function queries(): CollectionQueries
    {
        return new CollectionQueries($this, $this->mapper, $this->connection);
    }

    /**
     * Create a query builder for perform search on the collection
     *
     * @return MongoQuery<D>
     */
    public function query(): MongoQuery
    {
        return $this->queries()->query();
    }

    /**
     * Forward calls to mongo query
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->queries()->$name(...$arguments);
    }
}
