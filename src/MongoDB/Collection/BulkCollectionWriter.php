<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\Repository\Write\WriterInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use Bdf\Util\Arr;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;

/**
 * Writer for perform bulk operation
 *
 * All write operations are stacked unless `BulkCollectionWriter::flush()` is called
 *
 * <code>
 * $writer = $collection->writer();
 *
 * $writer->insert($doc1);
 * $writer->update($doc2, ['attributes' => ['foo', 'bar']]);
 * $writer->insert($doc3, ['replace' => 'true']);
 *
 * $writer->flush(); // 3
 * </code>
 *
 * @template D as object
 * @implements WriterInterface<D>
 *
 * @see MongoCollectionInterface::writer() For create the instance
 *
 * @todo BufferedWriteInterface sur prime
 */
class BulkCollectionWriter implements WriterInterface
{
    private MongoConnection $connection;
    private TypesRegistryInterface $types;
    private DocumentMapperInterface $mapper;
    private ?WriteQuery $query = null;

    /**
     * @param MongoCollectionInterface<D> $collection
     */
    public function __construct(MongoCollectionInterface $collection)
    {
        $this->mapper = $collection->mapper();
        $this->connection = $collection->connection();
        $this->types = $this->connection->platform()->types();
    }

    /**
     * {@inheritdoc}
     *
     * @todo handle ignore ?
     */
    public function insert($entity, array $options = []): int
    {
        $data = $this->mapper->toDatabase($entity, $this->types);
        $hasId = true;

        if (empty($data['_id'])) {
            $hasId = false;
            $data['_id'] = new ObjectId();
            $this->mapper->setId($entity, $data['_id']);
        }

        if (!$hasId || empty($options['replace'])) {
            $this->query()->insert($data);
        } else {
            $this->query()->update(['_id' => $data['_id']], $data, ['upsert' => true]);
        }

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity, array $options = []): int
    {
        if (($id = $this->mapper->getId($entity)) === null) {
            throw new InvalidArgumentException('The document id is missing');
        }

        // @todo optimise ? provide fields
        $data = $this->mapper->toDatabase($entity, $this->types);
        $fields = $options['attributes'] ?? null;

        if ($fields) {
            $changes = [];

            foreach ($fields as $field) {
                $changes[$field] = Arr::get($data, $field);
            }
        } else {
            $changes = $data;
        }

        $this->query()->update(['_id' => $id], ['$set' => $changes]);

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $options = []): int
    {
        $id = $this->mapper->getId($entity);

        if ($id === null) {
            return 0;
        }

        $this->query()->delete(['_id' => $id]);
        return 1;
    }

    /**
     * Get count of write operation waiting for application
     *
     * @return int
     */
    public function pending(): int
    {
        if (!$this->query) {
            return 0;
        }

        return $this->query->count();
    }

    /**
     * Apply all pending operations
     *
     * @return int Applied operations / affected documents
     */
    public function flush(): int
    {
        if ($query = $this->query) {
            $this->query = null;

            return $query->execute($this->connection)->count();
        }

        return 0;
    }

    public function __destruct()
    {
        $this->flush();
    }

    private function query(): WriteQuery
    {
        if ($this->query) {
            return $this->query;
        }

        return $this->query = new WriteQuery($this->mapper->collection());
    }
}
