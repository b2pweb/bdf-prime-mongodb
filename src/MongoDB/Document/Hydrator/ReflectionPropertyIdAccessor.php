<?php

namespace Bdf\Prime\MongoDB\Document\Hydrator;

use LogicException;
use MongoDB\BSON\ObjectId;
use ReflectionClass;
use ReflectionProperty;

/**
 * Read and write private or property "_id" property using Reflection
 *
 * @template D as object
 * @implements IdAccessorInterface<D>
 */
final class ReflectionPropertyIdAccessor implements IdAccessorInterface
{
    /**
     * @var class-string<D>
     */
    private string $className;

    /**
     * Cache reflection property
     *
     * @var ReflectionProperty|null
     */
    private ?ReflectionProperty $reflectionProperty = null;

    /**
     * @param class-string<D> $className Document class name
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function readId(object $document): ?ObjectId
    {
        return $this->getProperty()->getValue($document);
    }

    /**
     * {@inheritdoc}
     */
    public function writeId(object $document, ?ObjectId $id): void
    {
        $this->getProperty()->setValue($document, $id);
    }

    private function getProperty(): ReflectionProperty
    {
        if ($this->reflectionProperty) {
            return $this->reflectionProperty;
        }

        for ($reflection = new ReflectionClass($this->className); $reflection; $reflection = $reflection->getParentClass()) {
            if ($reflection->hasProperty('_id')) {
                $this->reflectionProperty = $reflection->getProperty('_id');
                $this->reflectionProperty->setAccessible(true);

                return $this->reflectionProperty;
            }
        }

        throw new LogicException('The primary key field "_id" is not declared on document class ' . $this->className);
    }
}
