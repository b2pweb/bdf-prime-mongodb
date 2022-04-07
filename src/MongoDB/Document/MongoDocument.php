<?php

namespace Bdf\Prime\MongoDB\Document;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use MongoDB\BSON\ObjectId;

/**
 * Base type for declare mongodb documents in PHP
 *
 * This class provide the "_id" field, and also active record capabilities
 *
 * @psalm-type DocumentQuery = MongoQuery<static>
 *
 * @method static static|null get(ObjectId $id)
 * @method static static|null findOneRaw(array $filters = [], array $options = [])
 * @method static iterable|static[]|null findAllRaw(array $filters = [], array $options = [])
 * @psalm-method static iterable<static>|null findAllRaw(array $filters = [], array $options = [])
 *
 * @method static MongoQuery where(string|array|callable $column, mixed|null $operator = null, mixed $value = null)
 * @psalm-method static DocumentQuery where(string|array|callable $column, mixed|null $operator = null, mixed $value = null)
 * @method static MongoQuery query()
 * @psalm-method static DocumentQuery query()
 *
 * @method static int count(array $criteria = [], $attributes = null)
 * @method static bool exists(self $entity)
 * @method static static|null refresh(self $entity)
 */
class MongoDocument
{
    /**
     * The document primary key
     * Note: do not rename, "_id" is the field name on mongo document
     *
     * @var ObjectId|null
     */
    protected ?ObjectId $_id = null; // phpcs:ignore

    /**
     * @return ObjectId|null
     */
    public function id(): ?ObjectId
    {
        return $this->_id;
    }

    /**
     * @param ObjectId|null $id
     * @return $this
     */
    public function setId(?ObjectId $id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * Save the document to the linked collection
     * This method will call replace(), so it performs an insert or replace write
     *
     * @return void
     *
     * @see MongoCollection::replace() The called method
     */
    public function save(): void
    {
        static::collection()->replace($this);
    }

    /**
     * Update given fields of the document to the collection
     * Other fields will not be changed
     *
     * @param list<string> $fields Fields to update. For embedded fields, use "dot" notation (i.e. "embedded.subField"). If empty, all fields will be updated.
     *
     * @return void
     *
     * @see MongoCollection::update() The called method
     */
    public function update(array $fields = []): void
    {
        static::collection()->update($this, $fields);
    }

    /**
     * Insert the document to the linked collection
     * This method will call add(), so it performs an insert, and will fail if the document already exists
     *
     * @return void
     *
     * @see MongoCollection::add() The called method
     *
     * @todo error si déjà existant
     */
    public function insert(): void
    {
        static::collection()->add($this);
    }

    /**
     * Delete the document from the linked collection
     *
     * @return void
     *
     * @see MongoCollection::delete() The called method
     */
    public function delete(): void
    {
        static::collection()->delete($this);
    }

    /**
     * Get the linked collection
     *
     * @return MongoCollection<static>
     */
    public static function collection(): MongoCollection
    {
        return Mongo::collection(static::class);
    }

    /**
     * Forward calls to collection instance
     * Note: only works if facade is configured for mongodb
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::collection()->$name(...$arguments);
    }
}
