<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator;

use MongoDB\BSON\ObjectId;

/**
 * Hydrator for simple stdClass document
 *
 * @implements IdAccessorInterface<\stdClass>
 */
final class StdClassDocumentHydrator implements DocumentHydratorInterface, IdAccessorInterface
{
    private static StdClassDocumentHydrator $instance;

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(object $document, array $data): object
    {
        foreach ($data as $k => $v) {
            $document->$k = $v;
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(object $document): array
    {
        return (array) $document;
    }

    /**
     * {@inheritdoc}
     */
    public function readId(object $document): ?ObjectId
    {
        return $document->_id ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function writeId(object $document, ?ObjectId $id): void
    {
        $document->_id = $id;
    }

    /**
     * Get the instance of StdClassDocumentHydrator
     *
     * @return StdClassDocumentHydrator
     */
    public static function instance(): StdClassDocumentHydrator
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
