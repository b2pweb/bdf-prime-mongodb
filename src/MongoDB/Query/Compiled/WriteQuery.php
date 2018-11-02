<?php

namespace Bdf\Prime\MongoDB\Query\Compiled;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\ResultSet\WriteResultSet;
use Bdf\Prime\MongoDB\Query\SelfExecutable;
use MongoDB\Driver\BulkWrite;

/**
 * Compiled query for perform write operations
 *
 * <code>
 * $query = $connection->builder();
 * $query->...; // Build update query
 * $compiled = $query->compile();
 *
 * $compiled->execute($connection)->count(); // Get the number of affected rows
 * </code>
 */
final class WriteQuery implements SelfExecutable
{
    /**
     * @var string
     */
    private $collection;

    /**
     * @var array
     */
    private $deletes = [];

    /**
     * @var array
     */
    private $updates = [];

    /**
     * @var array
     */
    private $inserts = [];

    /**
     * @var array
     */
    private $options = [];


    /**
     * WriteQuery constructor.
     *
     * @param string $collection
     * @param array $options
     */
    public function __construct($collection, array $options = [])
    {
        $this->collection = $collection;
        $this->options = $options;
    }

    /**
     * Change the ordered option
     *
     * If true, perform an ordered insert of the documents in the array, and if an error occurs with one of documents,
     * MongoDB will return without processing the remaining documents in the array.
     *
     * If false, perform an unordered insert, and if an error occurs with one of documents,
     * continue processing the remaining documents in the array.
     *
     * This parameter is the opposite of the IGNORE clause on SQL INSERT query
     *
     * @param bool $flag True for active ordered insert, or false
     *
     * @return $this
     *
     * @see https://docs.mongodb.com/v3.6/reference/method/db.collection.insert/#perform-an-unordered-insert
     */
    public function ordered($flag = true)
    {
        $this->options['ordered'] = $flag;

        return $this;
    }

    /**
     * Count expected roundtrips for executing the bulk
     * Returns the expected number of client-to-server roundtrips required to execute all write operations in the BulkWrite.
     *
     * @return int number of expected roundtrips to execute the BulkWrite.
     *
     * @see BulkWrite::count()
     */
    public function count()
    {
        return count($this->inserts) + count($this->updates) + count($this->deletes);
    }

    /**
     * Add a delete operation to the bulk
     *
     * @param array|object $filter The search filter
     * @param array $deleteOptions
     *
     * @return $this
     *
     * @see BulkWrite::delete()
     */
    public function delete($filter, array $deleteOptions = [])
    {
        $this->deletes[] = [$filter, $deleteOptions];

        return $this;
    }

    /**
     * Add an insert operation to the bulk
     *
     * @param array|object $document
     *
     * @return $this
     *
     * @see BulkWrite::insert()
     */
    public function insert($document)
    {
        $this->inserts[] = $document;

        return $this;
    }

    /**
     * Add an update operation to the bulk
     *
     * @param array|object $filter The search filter
     * @param array|object $newObj A document containing either update operators (e.g. $set) or a replacement document (i.e. only field:value expressions)
     * @param array $updateOptions
     *
     * @return $this
     *
     * @see BulkWrite::update()
     */
    public function update($filter, $newObj, array $updateOptions = [])
    {
        $this->updates[] = [$filter, $newObj, $updateOptions];

        return $this;
    }

    /**
     * Merge a write query into the current
     * The current query will contain all operation of the merged query, and the current one
     *
     * @param WriteQuery $query
     *
     * @return $this
     */
    public function merge(WriteQuery $query)
    {
        $this->inserts = array_merge($this->inserts, $query->inserts);
        $this->updates = array_merge($this->updates, $query->updates);
        $this->deletes = array_merge($this->deletes, $query->deletes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(MongoConnection $connection)
    {
        $bulk = new BulkWrite($this->options);

        foreach ($this->inserts as $insert) {
            $bulk->insert($insert);
        }

        foreach ($this->updates as $update) {
            $bulk->update(...$update);
        }

        foreach ($this->deletes as $delete) {
            $bulk->delete(...$delete);
        }

        return new WriteResultSet($connection->executeWrite($this->collection, $bulk));
    }
}
