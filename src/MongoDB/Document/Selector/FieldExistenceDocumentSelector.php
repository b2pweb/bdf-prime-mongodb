<?php

namespace Bdf\Prime\MongoDB\Document\Selector;

/**
 * Select document class by checking existence of fields
 *
 * This selector will use `array_key_exists()` and `$exists` for select matching documents.
 *
 * Note: This selector may have important performance impacts, because some queries can result to a full collection scan.
 *       Prefer use `DiscriminatorFieldDocumentSelector` when introduction of a new field is possible.
 *
 * @see https://www.mongodb.com/docs/manual/reference/operator/query/exists/
 * @see array_key_exists()
 *
 * @template D as object
 * @implements DocumentSelectorInterface<D>
 */
final class FieldExistenceDocumentSelector implements DocumentSelectorInterface
{
    /**
     * Base class name
     * Will be used if the discriminator field is not provided, or do not match with configured mapping
     *
     * @var class-string<D>
     */
    private string $documentClass;

    /**
     * Mapping of fields, with class name as key and list of fields as value
     *
     * @var array<class-string<D>, list<string>>
     */
    private array $mapping;

    /**
     * @param array<class-string<D>, list<string>> $mapping Mapping of fields, with class name as key and list of fields as value
     */
    public function __construct(string $documentClass, array $mapping)
    {
        $this->documentClass = $documentClass;
        $this->mapping = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function instantiate(array $data): object
    {
        foreach ($this->mapping as $documentClass => $fields) {
            foreach ($fields as $field) {
                if (!array_key_exists($field, $data)) {
                    continue 2;
                }
            }

            return new $documentClass();
        }

        $documentClass = $this->documentClass;

        return new $documentClass();
    }

    /**
     * {@inheritdoc}
     */
    public function filters(string $documentClass): array
    {
        $fields = $this->mapping[$documentClass] ?? null;

        if (!$fields) {
            return [];
        }

        return array_fill_keys($fields, ['$exists' => true]);
    }
}
