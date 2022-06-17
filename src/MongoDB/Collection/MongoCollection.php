<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\Repository\Write\BufferedWriterInterface;
use MongoDB\BSON\ObjectId;

/**
 * Store and access to mongo documents
 *
 * @template D as object
 * @implements MongoCollectionInterface<D>
 *
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
        $writer = new BulkCollectionWriter($this);
        $writer->insert($document);
        $writer->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function replace(object $document): void
    {
        $writer = new BulkCollectionWriter($this);
        $writer->insert($document, ['replace' => true]);
        $writer->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function update(object $document, array $fields = []): void
    {
        $writer = new BulkCollectionWriter($this);
        $writer->update($document, ['attributes' => $fields]);
        $writer->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(object $document): void
    {
        if ($document instanceof ObjectId) {
            (new WriteQuery($this->mapper->collection()))
                ->delete(['_id' => $document])
                ->execute($this->connection)
            ;
            return;
        }

        $writer = new BulkCollectionWriter($this);
        $writer->delete($document);
        $writer->flush();
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
    public function writer(): BufferedWriterInterface
    {
        return new BulkCollectionWriter($this);
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
