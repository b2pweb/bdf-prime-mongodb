<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator;


use MongoDB\BSON\ObjectId;

/**
 * Handle hydration and extraction for mongo documents
 *
 * @template D as object
 */
interface IdAccessorInterface
{
    /**
     * Read the "_id" field (i.e. primary key) from the document
     *
     * @param D $document Document to read
     *
     * @return ObjectId|null The extract id if present
     */
    public function readId(object $document): ?ObjectId;

    /**
     * Write the "_id" field (i.e. primary key) to the document
     *
     * @param D $document Document to write
     */
    public function writeId(object $document, ?ObjectId $id): void;
}
