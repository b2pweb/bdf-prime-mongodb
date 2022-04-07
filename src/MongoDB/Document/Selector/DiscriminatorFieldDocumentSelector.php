<?php

namespace Bdf\Prime\MongoDB\Document\Selector;

/**
 * Instantiator using a discrimiator field, and class mapping for select the document class
 * The default constructor will be used to instantiate the corresponding class
 *
 * Note: all document classes must inherit from a base class
 *
 * @template D as object
 * @implements DocumentSelectorInterface<D>
 */
final class DiscriminatorFieldDocumentSelector implements DocumentSelectorInterface
{
    /**
     * Base class name
     * Will be used if the discriminator field is not provided, or do not match with configured mapping
     *
     * @var class-string<D>
     */
    private string $documentClass;

    /**
     * Discriminator mapping, with discriminator value as key, and document class as value
     *
     * @var array<array-key, class-string<D>>
     */
    private array $mapping;

    /**
     * Maps discriminator values by the target document class
     * This map is the reverse of `$this->mapping`
     *
     * @var array<class-string<D>, list<array-key>>
     */
    private array $discriminatorByDocumentClass;

    /**
     * @var string
     */
    private string $discriminatorField;

    /**
     * @param class-string<D> $documentClass Base class name. Will be used if the discriminator field is not provided, or do not match with configured mapping.
     * @param array<array-key, class-string<D>> $mapping Discriminator mapping, with discriminator value as key, and document class as value.
     * @param string $discriminatorField The field name used as discriminator
     */
    public function __construct(string $documentClass, array $mapping, string $discriminatorField = '_type')
    {
        $this->documentClass = $documentClass;
        $this->mapping = $mapping;
        $this->discriminatorField = $discriminatorField;

        $this->discriminatorByDocumentClass = [];

        foreach ($mapping as $discriminator => $documentClass) {
            $this->discriminatorByDocumentClass[$documentClass][] = $discriminator;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function instantiate(array $data): object
    {
        $discriminator = $data[$this->discriminatorField] ?? null;

        if (is_scalar($discriminator)) {
            $documentClass = $this->mapping[$discriminator] ?? $this->documentClass;
        } else {
            $documentClass = $this->documentClass;
        }

        return new $documentClass();
    }

    /**
     * {@inheritdoc}
     */
    public function filters(string $documentClass): array
    {
        $discriminators = $this->discriminatorByDocumentClass[$documentClass] ?? null;

        if (empty($discriminators)) {
            return [];
        }

        if (count($discriminators) === 1) {
            return [$this->discriminatorField => $discriminators[0]];
        }

        return [$this->discriminatorField => ['$in' => $discriminators]];
    }
}
