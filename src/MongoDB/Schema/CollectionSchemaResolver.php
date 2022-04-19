<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\Schema\ResolverInterface;

/**
 * Resolve CollectionResolver instance
 */
class CollectionSchemaResolver
{
    private MongoCollectionLocator $locator;

    /**
     * @param MongoCollectionLocator $locator
     */
    public function __construct(MongoCollectionLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Get a schema resolver instance from a mapper class
     *
     * @param class-string $mapperClass The mapper class name
     *
     * @return ResolverInterface|null Resolver instance, or null if the class name is not a valid mapper class
     */
    public function resolveByMapperClass(string $mapperClass): ?ResolverInterface
    {
        if (!is_subclass_of($mapperClass, DocumentMapperInterface::class)) {
            return null;
        }

        return new CollectionResolver($this->locator->collectionByMapper($mapperClass));
    }

    /**
     * Get a schema resolver instance from a document class
     *
     * @param class-string $documentClass
     *
     * @return ResolverInterface Resolver instance
     */
    public function resolveByDocumentClass(string $documentClass): ResolverInterface
    {
        return new CollectionResolver($this->locator->collection($documentClass));
    }
}
