<?php

namespace Bdf\Prime\MongoDB\Document;

use Bdf\Prime\MongoDB\Document\Mapping\FieldsMapping;
use Bdf\Prime\Types\TypesRegistryInterface;
use MongoDB\BSON\ObjectId;

/**
 * Class DocumentMapper
 *
 * @template D as object
 */
interface DocumentMapperInterface
{
    /**
     * @return string
     */
    public function connection(): string;

    /**
     * @return string
     */
    public function collection(): string;

    /**
     * @param D $document
     * @return array
     */
    public function toDatabase(object $document, TypesRegistryInterface $types): array;

    /**
     * @param array $data
     * @return D
     */
    public function fromDatabase(array $data, TypesRegistryInterface $types): object;

    /**
     * @param D $document
     * @return ObjectId|null
     */
    public function getId(object $document): ?ObjectId;

    /**
     * @param D $document
     * @param ObjectId|null $id
     * @return void
     */
    public function setId(object $document, ?ObjectId $id): void;

    /**
     * Get base filters to apply on queries
     *
     * @return array
     */
    public function constraints(): array;

    public function fields(): FieldsMapping;

    /**
     * @return array<string, callable>
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
     */
    public function queries(): array;
}
