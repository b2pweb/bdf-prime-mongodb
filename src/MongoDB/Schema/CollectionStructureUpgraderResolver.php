<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\Schema\StructureUpgraderInterface;
use Bdf\Prime\Schema\StructureUpgraderResolverInterface;

/**
 * Resolve CollectionStructureUpgrader instance
 */
class CollectionStructureUpgraderResolver implements StructureUpgraderResolverInterface
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
     * {@inheritdoc}
     */
    public function resolveByMapperClass(string $mapperClassName, bool $force = false): ?StructureUpgraderInterface
    {
        if (!is_subclass_of($mapperClassName, DocumentMapperInterface::class)) {
            return null;
        }

        return new CollectionStructureUpgrader($this->locator->collectionByMapper($mapperClassName));
    }

    /**
     * {@inheritdoc}
     */
    public function resolveByDomainClass(string $className, bool $force = false): ?StructureUpgraderInterface
    {
        return new CollectionStructureUpgrader($this->locator->collection($className));
    }
}
