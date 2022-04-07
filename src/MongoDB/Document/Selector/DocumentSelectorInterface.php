<?php

namespace Bdf\Prime\MongoDB\Document\Selector;

/**
 * Type for select matching document class from database values, or apply filters on queries
 *
 * @template D as object
 */
interface DocumentSelectorInterface
{
    /**
     * Instantiate the document corresponding to given data
     *
     * Note: This is not an hydrator, and should not hydrate properties following $data parameter.
     *       This parameter should be used as discriminator, to select matching document class.
     *
     * @param array $data
     * @return D
     */
    public function instantiate(array $data): object;

    /**
     * Get discriminator filters used for the given document class
     *
     * @param class-string<D> $documentClass
     *
     * @return array MongoDB filters
     */
    public function filters(string $documentClass): array;
}
