<?php

namespace Bdf\Prime\MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;

/**
 *
 */
interface DocumentMapperClassResolverInterface
{
    /**
     * @param class-string $documentClassName
     * @return class-string<DocumentMapperInterface>|null
     */
    public function resolveByDocumentClass(string $documentClassName): ?string;

    /**
     * @param class-string<DocumentMapperInterface> $mapperClassName
     * @return class-string
     */
    public function resolveDocumentClassByMapperClass(string $mapperClassName): string;
}
