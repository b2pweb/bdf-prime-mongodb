<?php

namespace Bdf\Prime\MongoDB\Document\Mapping;

use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use Bdf\Util\Arr;

class FieldsMapping
{
    /**
     * @var array<string, Field|array>
     */
    private array $fields = [];

    /**
     * @param array<string, Field|array> $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Convert mongo fields values to PHP values
     *
     * @param array $values DB values
     *
     * @return array
     */
    public function fromDatabase(array $values, TypesRegistryInterface $types): array
    {
        return $this->mapToPhp($types, $this->fields, $values);
    }

    /**
     * Convert PHP properties values to Mongo fields values
     *
     * @param array $values PHP values
     *
     * @return array
     */
    public function toDatabase(array $values, TypesRegistryInterface $types): array
    {
        return $this->mapFromPhp($types, $this->fields, $values);
    }

    /**
     * Get type of declared field
     *
     * @param string $field Field name. use "dot" notation for get an embedded field
     * @param TypesRegistryInterface $types
     *
     * @return TypeInterface|null The field type if defined
     */
    public function typeOf(string $field, TypesRegistryInterface $types): ?TypeInterface
    {
        $field = Arr::get($this->fields, $field);

        if (!$field instanceof Field) {
            return null;
        }

        return $types->get($field->type());
    }

    /**
     * @param array<string, Field|array> $declaredFields
     * @param array $dbFields
     *
     * @return array
     */
    private function mapToPhp(TypesRegistryInterface $types, array $declaredFields, array $dbFields): array
    {
        $phpValues = [];

        foreach ($dbFields as $field => $value) {
            $fieldMapping = $declaredFields[$field] ?? null;

            // array_is_list() @todo check array is list for handle array fields
            if (is_array($fieldMapping)) {
                $value = $this->mapToPhp($types, $fieldMapping, (array) $value);
            } elseif ($fieldMapping instanceof Field) {
                $value = $fieldMapping->fromDatabase($value, $types);
            }

            $phpValues[$field] = $value;
        }

        return $phpValues;
    }

    /**
     * @param array<string, Field|array> $declaredFields
     * @param array $phpValues
     *
     * @return array
     */
    private function mapFromPhp(TypesRegistryInterface $types, array $declaredFields, array $phpValues): array
    {
        $dbFields = [];

        foreach ($phpValues as $field => $value) {
            $fieldMapping = $declaredFields[$field] ?? null;

            // array_is_list() @todo check array is list for handle array fields
            if (is_array($fieldMapping)) {
                $value = $this->mapFromPhp($types, $fieldMapping, (array) $value);
            } elseif ($fieldMapping instanceof Field) {
                $value = $fieldMapping->toDatabase($value, $types);
            }

            $dbFields[$field] = $value;
        }

        return $dbFields;
    }
}
