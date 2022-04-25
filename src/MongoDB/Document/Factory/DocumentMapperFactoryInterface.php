<?php

namespace Bdf\Prime\MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;

/**
 * Resolve and create document mappers
 */
interface DocumentMapperFactoryInterface
{
    /**
     * Create a mapper by the related document class name
     *
     * @param class-string<D> $documentClassName Document class name
     *
     * @return DocumentMapperInterface<D>|null The mapper instance, or null if no mapper are related to the given document class
     *
     * @template D as object
     */
    public function createByDocumentClassName(string $documentClassName): ?DocumentMapperInterface;

    /**
     * Create a mapper by its class
     *
     * @param class-string<DocumentMapperInterface> $mapperClassName Mapper to create
     * @param class-string<D>|null $documentClassName Related document class. If not provided, it will be resolved from the mapper class.
     *
     * @return DocumentMapperInterface<D> The mapper instance
     *
     * @template D as object
     */
    public function createByMapperClassName(string $mapperClassName, ?string $documentClassName = null): DocumentMapperInterface;
}
