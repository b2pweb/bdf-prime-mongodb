<?php

namespace Bdf\Prime\MongoDB\Document\Mapping;

use Bdf\Prime\Types\TypeInterface;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use MongoDB\BSON\Binary;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Parse class properties for declare mongo fields
 */
class ClassParser
{
    /**
     * Map PHP type to mongo type name
     *
     * @var array<string, string>
     */
    private array $autoConfigureTypes = [
        DateTimeInterface::class => TypeInterface::DATETIME,
        DateTime::class => TypeInterface::DATETIME,
        DateTimeImmutable::class => TypeInterface::DATETIME,
        Binary::class => TypeInterface::BINARY,

        'bool' => TypeInterface::BOOLEAN,
        'float' => TypeInterface::DOUBLE,
        'int' => TypeInterface::INTEGER,
        'string' => TypeInterface::STRING,
        'object' => TypeInterface::OBJECT,
    ];

    /**
     * Parse fields from a class using reflection
     *
     * @param ReflectionClass $class
     * @return array<string, Field|array>
     *
     * @throws \ReflectionException
     */
    public function parse(ReflectionClass $class): array
    {
        $fields = [];

        do {
            foreach ($class->getProperties() as $property) {
                if (isset($fields[$property->getName()]) || $property->isStatic() || (!$type = $property->getType()) || (!$type instanceof ReflectionNamedType)) {
                    continue;
                }

                $fieldType = $this->resolveType($type->getName());

                if ($fieldType) {
                    $fields[$property->getName()] = new Field($property->getName(), $fieldType, $type->getName());
                    continue;
                }

                if (!$type->isBuiltin()) {
                    $fieldClass = new ReflectionClass($type->getName());

                    if ($fieldClass->isUserDefined()) {
                        $fields[$property->getName()] = $this->parse($fieldClass);
                    }
                }
            }
        } while ($class = $class->getParentClass());

        return $fields;
    }

    /**
     * Get the matching mongo field type from PHP type
     *
     * @param string $phpType PHP type name. Can be a class name or a native type name
     *
     * @return string|null The field type, or null is not found
     */
    public function resolveType(string $phpType): ?string
    {
        $type = $this->autoConfigureTypes[$phpType] ?? null;

        if ($type) {
            return $type;
        }

        if (class_exists($phpType)) {
            foreach ($this->autoConfigureTypes as $typeName => $type) {
                if (is_subclass_of($phpType, $typeName)) {
                    return $type;
                }
            }
        }

        return null;
    }
}
