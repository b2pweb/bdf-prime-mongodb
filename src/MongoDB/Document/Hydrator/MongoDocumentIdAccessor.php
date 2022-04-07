<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator;

use Bdf\Prime\MongoDB\Document\MongoDocument;
use MongoDB\BSON\ObjectId;

/**
 * Read and write id on a subclass of MongoDocument, by using accessors
 *
 * @implements IdAccessorInterface<MongoDocument>
 */
final class MongoDocumentIdAccessor implements IdAccessorInterface
{
    private static MongoDocumentIdAccessor $instance;

    /**
     * {@inheritdoc}
     *
     * @param MongoDocument $document
     */
    public function readId(object $document): ?ObjectId
    {
        return $document->id();
    }

    /**
     * {@inheritdoc}
     *
     * @param MongoDocument $document
     */
    public function writeId(object $document, ?ObjectId $id): void
    {
        $document->setId($id);
    }

    /**
     * Get the instance of MongoDocumentIdAccessor
     *
     * @return MongoDocumentIdAccessor
     */
    public static function instance(): MongoDocumentIdAccessor
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
