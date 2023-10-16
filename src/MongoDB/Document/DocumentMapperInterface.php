<?php

namespace Bdf\Prime\MongoDB\Document;

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\MongoDB\Collection\MongoCollectionInterface;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMapping;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Schema\CollectionDefinition;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use MongoDB\BSON\ObjectId;

/**
 * Base type for declare a mongo collection
 *
 * @template D as object
 *
 * @method array<string, callable> filters() Get custom filters for this collection
 */
interface DocumentMapperInterface
{
    /**
     * Configure the mapper for the given document class
     * A new instance of the mapper will be returned : the previous instance will be unchanged
     *
     * @param class-string<R> $documentClassName New document class name
     * @return DocumentMapperInterface<R> New mapper instance
     *
     * @template R as object
     */
    public function forDocument(string $documentClassName): self;

    /**
     * Get the related document class name
     *
     * @return class-string<D>
     */
    public function document(): string;

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
     * Create the mongo collection instance related to the current mapper
     *
     * @param MongoConnection $connection
     *
     * @return MongoCollectionInterface<D>
     */
    public function createMongoCollection(MongoConnection $connection): MongoCollectionInterface;

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

    /**
     * Get custom filters for this collection
     *
     * A filter is a callable which can be used as "column" parameter on where method call,
     * and which will be called to build the mongo filter.
     * so, you can use filters for declare common criteria, or to hide complex mongo filter.
     *
     * Each filters will take as first argument the query instance, and the second argument is the value passed to where method.
     * The filter should return nothing.
     *
     * <code>
     *  // Method body:
     *  return [
     *      'inSearch' => function (MongoQuery $query, $search): void {
     *          $query->where('search', ':in', $search);
     *      },
     *      'complex' => function (MongoQuery $collection, $search) {
     *           $query->whereRaw([
     *             '$or' => [
     *                  ['name' => ['$regex' => $search]],
     *                  ['description' => ['$regex' => $search]],
     *              ]
     *           ]);
     *       },
     *  ];
     *
     *  // Usage:
     *  MyEntity::where('complex', 'foo')->all();
     *  MyEntity::where('inSearch', ['foo', 'bar'])->all();
     * </code>
     *
     * @return array<string, callable(QueryInterface, mixed):void>
     *
     * @see Mapper::filters() Equivalent on prime ORM
     */
    //public function filters(): array;
}
