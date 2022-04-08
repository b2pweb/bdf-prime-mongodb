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
 * @implements MongoCollectionInterface<D>
 *
 * @todo gestion des exceptions
 * @todo lazy connection
 */
class MongoCollection implements MongoCollectionInterface
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function get(ObjectId $id): ?object
    {
        return $this->findOneRaw(['_id' => $id]);
    }

    /**
     * {@inheritdoc}
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
            return $row['n'] > 0;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(object $document): ?object
    {
        $id = $this->mapper->getId($document);

        return $id ? $this->get($id) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAllRaw(array $filters = [], array $options = []): array
    {
        $filters += $this->mapper->constraints();
        $query = new ReadQuery($this->mapper->collection(), $filters, $options);
        $result = $query->execute($this->connection);

        $mapper = $this->mapper;
        $types = $this->connection->platform()->types();
        $documents = [];

        foreach ($result->asRawArray() as $document) {
            $documents[] = $mapper->fromDatabase($document, $types);
        }

        return $documents;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function count(array $filters = []): int
    {
        return $this->query()->where($filters)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function connection(): MongoConnection
    {
        return $this->connection;
    }

    public function mapper(): DocumentMapperInterface
    {
        return $this->mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function queries(): CollectionQueries
    {
        return new CollectionQueries($this, $this->mapper, $this->connection);
    }

    /**
     * {@inheritdoc}
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
