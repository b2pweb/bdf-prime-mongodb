<?php

namespace Bdf\Prime\MongoDB\Document\Selector;

/**
 * Base instantiator, using default constructor
 *
 * @template D as object
 * @implements DocumentSelectorInterface<D>
 */
final class DefaultDocumentSelector implements DocumentSelectorInterface
{
    /**
     * @var class-string<D>
     */
    private string $documentClass;

    /**
     * @param class-string<D> $documentClass
     */
    public function __construct(string $documentClass)
    {
        $this->documentClass = $documentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function instantiate(array $data): object
    {
        $className = $this->documentClass;

        return new $className();
    }

    /**
     * {@inheritdoc}
     */
    public function filters(string $documentClass): array
    {
        return [];
    }
}
