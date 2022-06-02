<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\Repository\Write\WriterInterface;
use MongoDB\BSON\ObjectId;

/**
 * Store and access to mongo documents
 *
 * @template D as object
 * @mixin MongoQuery<D>
 *
 * @todo gestion des exceptions
 * @todo lazy connection
 */
interface MongoCollectionInterface
{
    /**
     * Add the document to the collection
     * If the id is not provided, it will be generated
     * If the document already exists, this method will fail
     *
     * @param D $document
     * @return void
     */
    public function add(object $document): void;

    /**
     * Replace the document on the collection
     * If the id is not provided, it will be generated, and perform a simple insert
     * If the document already exist, this method will replace the document
     * If the document do not exist, it will be inserted
     *
     * @param D $document
     * @return void
     */
    public function replace(object $document): void;

    /**
     * Perform a simple update of the document
     *
     * @param D $document Document to update
     * @param list<string> $fields List of fields to update. For embedded fields, use "dot" notation (i.e. "embedded.subField"). If empty, all fields will be updated.
     *
     * @return void
     */
    public function update(object $document, array $fields = []): void;

    /**
     * Delete the document from the collection
     *
     * If the document has no id, or do not exist, this will do nothing
     *
     * @param D $document
     * @return void
     *
     * @todo allow delete by id
     */
    public function delete(object $document): void;

    /**
     * Get a document by its id
     *
     * @param ObjectId $id
     *
     * @return D|null The document, or null if not exists
     */
    public function get(ObjectId $id): ?object;

    /**
     * Check the existence of the document in the collection
     * This method will only check the "_id" field.
     *
     * @param D $document Document to check
     * @return bool true if exists
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function exists(object $document): bool;

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
    public function refresh(object $document): ?object;

    /**
     * Perform a search on the collection
     *
     * @param array $filters Raw mongodb filters
     * @param array $options Search options
     *
     * @return D[]
     */
    public function findAllRaw(array $filters = [], array $options = []): array;

    /**
     * Perform a search on the collection, and return a single entity
     *
     * @param array $filters Raw mongodb filters
     * @param array $options Search options
     *
     * @return D|null The document if exists, or null
     */
    public function findOneRaw(array $filters = [], array $options = []): ?object;

    /**
     * Count matching documents on the collection
     *
     * @param array $filters Criteria
     * @return int
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function count(array $filters = []): int;

    /**
     * Get the connection which store the collection
     *
     * @return MongoConnection
     */
    public function connection(): MongoConnection;

    /**
     * Collection mapper
     *
     * @return DocumentMapperInterface
     */
    public function mapper(): DocumentMapperInterface;

    /**
     * Get factory of queries linked to the current collection
     *
     * @return CollectionQueries<D>
     */
    public function queries(): CollectionQueries;

    /**
     * Get buffered writer for perform bulk write to collection
     * Note: a new instance is always returned by this method
     *
     * @return WriterInterface<D>
     * @todo BufferedWriterInterface
     */
    public function writer(): WriterInterface;

    /**
     * Create a query builder for perform search on the collection
     *
     * @return MongoQuery<D>
     */
    public function query(): MongoQuery;
}
