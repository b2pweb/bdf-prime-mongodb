<?php

namespace Bdf\Prime\MongoDB\Test;

use Bdf\Prime\MongoDB\Collection\MongoCollectionInterface;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Schema\CollectionStructureUpgraderResolver;

/**
 * Utility class for handle mongodb documents creation and purge for testing
 * Note: do not use this class on production !
 */
class MongoTester implements \ArrayAccess
{
    private MongoCollectionLocator $locator;
    private CollectionStructureUpgraderResolver $schemaResolver;

    /**
     * @var array<class-string, class-string>
     */
    private array $declaredCollections = [];

    /**
     * @var array<string, object>
     */
    private array $documents = [];

    /**
     * @param MongoCollectionLocator|null $locator
     */
    public function __construct(?MongoCollectionLocator $locator = null)
    {
        $this->locator = $locator ?? Mongo::locator();
        $this->schemaResolver = new CollectionStructureUpgraderResolver($this->locator);
    }

    /**
     * Declare collections for given documents
     * This method will declare indexes, and store created collections for drop when calling `MongoTester::destroy()`
     *
     * @param class-string ...$documentClasses
     * @return $this
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function declare(string ...$documentClasses): self
    {
        foreach ($documentClasses as $documentClass) {
            if (!isset($this->declaredCollections[$documentClass])) {
                $this->declaredCollections[$documentClass] = $documentClass;
                $this->schemaResolver->resolveByDomainClass($documentClass)->migrate(); // @todo check null ?
            }
        }

        return $this;
    }

    /**
     * Push documents into collections
     * This method will automatically declare collections
     *
     * @param object|object[] $documents Documents to save. Use string key to keep documents instances accessible using `MongoTester::get()`.
     * @return $this
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function push($documents): self
    {
        if (!is_array($documents)) {
            $documents = [$documents];
        }

        foreach ($documents as $name => $document) {
            $this->declare(get_class($document));
            $this->collection($document)->replace($document);

            if (is_string($name)) {
                $this->documents[$name] = $document;
            }
        }

        return $this;
    }

    /**
     * Get a pushed document by its name
     *
     * @param string $name
     *
     * @return object
     */
    public function get(string $name): object
    {
        return $this->documents[$name];
    }

    /**
     * Retrieve database version of the document
     *
     * @param D $document Document to refresh
     *
     * @return D|null Database document. Null if not found
     *
     * @template D as object
     */
    public function refresh(object $document): ?object
    {
        return $this->collection($document)->refresh($document);
    }

    /**
     * Get a collection from the document class name or instance
     *
     * @param class-string<D>|D $documentClass
     *
     * @return MongoCollectionInterface<D>
     *
     * @template D as object
     */
    public function collection($documentClass): MongoCollectionInterface
    {
        return $this->locator->collection(is_object($documentClass) ? get_class($documentClass) : $documentClass);
    }

    /**
     * Drop all declared collections and clear stored documents
     *
     * @return $this
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function destroy(): self
    {
        foreach ($this->declaredCollections as $documentClass) {
            $this->schemaResolver->resolveByDomainClass($documentClass)->drop();
        }

        $this->declaredCollections = [];
        $this->documents = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if (is_object($offset)) {
            return $this->collection($offset)->exists($offset);
        }

        return isset($this->documents[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (is_object($offset)) {
            return $this->collection($offset)->refresh($offset);
        }

        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (is_string($offset)) {
            $this->push([$offset => $value]);
        } else {
            $this->push($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if (is_string($offset)) {
            $document = $this->get($offset);
            unset($this->documents[$offset]);
        } else {
            $document = $offset;
        }

        $this->collection($document)->delete($document);
    }
}
