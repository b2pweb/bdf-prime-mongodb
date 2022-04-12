<?php

namespace Bdf\Prime\MongoDB\Document;

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMapping;
use Bdf\Prime\MongoDB\Schema\CollectionDefinition;
use Bdf\Prime\Types\TypesRegistryInterface;
use MongoDB\BSON\ObjectId;

/**
 * Base type for declare a mongo collection
 *
 * @template D as object
 */
interface DocumentMapperInterface
{
    /**
     * Get the connection name which stores the collection
     *
     * @return string
     */
    public function connection(): string;

    /**
     * Get the collection name
     *
     * @return string
     */
    public function collection(): string;

    /**
     * Convert a document object to mongo fields
     *
     * @param D $document Document to convert
     * @param TypesRegistryInterface $types Types registered on the connection
     *
     * @return array Mongo document
     */
    public function toDatabase(object $document, TypesRegistryInterface $types): array;

    /**
     * Get PHP document instance from mongo fields
     *
     * @param array $data Mongo fields value
     *
     * @return D Document instance. Can be a subclass of D if a DocumentSelector is used.
     */
    public function fromDatabase(array $data, TypesRegistryInterface $types): object;

    /**
     * Extract document ID from document object
     *
     * @param D $document
     *
     * @return ObjectId|null The ID or null if not provided
     */
    public function getId(object $document): ?ObjectId;

    /**
     * Write document ID to the document object
     *
     * @param D $document Document to write on
     * @param ObjectId|null $id ID to define. Can be null to unset ID
     *
     * @return void
     */
    public function setId(object $document, ?ObjectId $id): void;

    /**
     * Get base filters to apply on queries
     * Note: this is raw mongo filter, which is not converted by compiler. Use whereRa() to apply on a query
     *
     * @return array
     */
    public function constraints(): array;

    /**
     * Get declared fields mapping
     * Unlike prime ORM, fields declaration is not required, and undeclared fields can be used without constraints.
     *
     * @return FieldsMapping
     */
    public function fields(): FieldsMapping;

    /**
     * Get collection definition
     * Contains collection options and indexes
     *
     * @return CollectionDefinition
     */
    public function definition(): CollectionDefinition;

    /**
     * Extension for mongo query for adding extra methods on collection or repository
     *
     * <code>
     * return [
     *     'customMethod' => function(MongoQuery $query, $test) {
     *         return $query->where('foo', $test)->first();
     *     },
     * ];
     *
     * $repository->customMethod('test');
     * $repository->where('bar', 123)->customMethod('test');
     * </code>
     *
     * @return array<string, callable>
     *
     * @see Mapper::scopes() Equivalent on prime ORM
     */
    public function scopes(): array;

    /**
     * Get custom queries for repository
     * A custom query works mostly like scopes, but with some differences :
     * - Cannot be called using a query (i.e. $query->where(...)->myScope())
     * - The function has responsability of creating the query instance
     * - The first argument is the repository
     *
     * <code>
     * return [
     *     'findByCustom' => function (MongoCollection $collection, $search) {
     *         return $collection->queries()->make(MyCustomQuery::class)->where('first', $search)->first();
     *     },
     *     'rawSearch' => function (MongoCollection $collection, $search) {
     *         return $collection->findAllRaw(['search' => ['$in' => $search]]);
     *     },
     * ];
     * </code>
     *
     * @return array<string, callable(\Bdf\Prime\MongoDB\Collection\MongoCollectionInterface<D>,mixed...):mixed>
     *
     * @see Mapper::queries() Equivalent on prime ORM
     */
    public function queries(): array;
}
