<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator;

use Bdf\Serializer\SerializerInterface;
use stdClass;

/**
 * Simple factory for create hydrator instance
 */
final class DocumentHydratorFactory
{
    private static ?DocumentHydratorFactory $instance = null;

    private ?SerializerInterface $serializer;
    private ?BdfDocumentHydrator $serializedHydrator = null;

    /**
     * @param SerializerInterface|null $serializer
     */
    public function __construct(?SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer;
    }

    /**
     * Create the hydrator instance for the given document class
     *
     * @param class-string $documentBaseClass
     *
     * @return DocumentHydratorInterface
     */
    public function create(string $documentBaseClass): DocumentHydratorInterface
    {
        if ($documentBaseClass === stdClass::class) {
            return StdClassDocumentHydrator::instance();
        }

        if ($this->serializedHydrator) {
            return $this->serializedHydrator;
        }

        return $this->serializedHydrator = new BdfDocumentHydrator($this->serializer);
    }

    /**
     * Get instance of default hydrator factory
     *
     * @return DocumentHydratorFactory
     */
    public static function instance(): DocumentHydratorFactory
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self();
    }
}
