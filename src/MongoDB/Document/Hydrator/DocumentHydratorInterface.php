<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator;

/**
 * Handle hydration and extraction for mongo documents
 */
interface DocumentHydratorInterface
{
    /**
     * Fill the document with database values
     *
     * @param D $document Target document to hydrate
     * @param array $data Database data
     *
     * @return D
     *
     * @template D as object
     */
    public function fromDatabase(object $document, array $data): object;

    /**
     * Extract database fields from the document
     *
     * @param object $document Document to extract
     *
     * @return array Database fields
     */
    public function toDatabase(object $document): array;

}
