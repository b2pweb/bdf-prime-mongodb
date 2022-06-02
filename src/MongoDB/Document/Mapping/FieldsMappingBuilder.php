<?php

namespace Bdf\Prime\MongoDB\Document\Mapping;

use Bdf\Prime\Types\TypeInterface;
use DateTimeInterface;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use ReflectionClass;

class FieldsMappingBuilder
{
    /**
     * @var array<string, Field|array>
     */
    private array $fields = [];
    private ?ClassParser $parser = null;

    /**
     * Declare a new field
     *
     * @param string $name The field name
     * @param string $type Mongo field type
     * @param string|null $phpType PHP property type. Can be a type name or a class name
     *
     * @return $this
     */
    public function add(string $name, string $type, ?string $phpType = null): self
    {
        $this->fields[$name] = new Field($name, $type, $phpType);

        return $this;
    }

    /**
     * Declare an UTCDateTime mongo field
     *
     * @param string $name The field name
     * @param class-string<DateTimeInterface>|class-string<UTCDateTime>|null $phpType PHP property type. If null DateTime will be used.
     *
     * @return $this
     */
    public function dateTime(string $name, ?string $phpType = null): self
    {
        return $this->add($name, TypeInterface::DATETIME, $phpType);
    }
//
//    /**
//     * Register an object mongo field
//     * In PHP, a mongo object can be represented using a class, a simple object or an associative array
//     *
//     * @param string $name The field name
//     * @param callable(FieldsMappingBuilder):void $configurator Configure inner object fields
//     * @param class-string|null $className
//     *
//     * @return $this
//     */
//    public function object(string $name, callable $configurator, ?string $className = null): self
//    {
//        $builder = new FieldsMappingBuilder();
//        $configurator($builder);
//
//        return $this->add($name, new ObjectType($builder->fields), $className);
//    }

    /**
     * Register a binary field
     *
     * @param string $name The field name
     * @param Binary::TYPE_* $type Binary type
     *
     * @return $this
     */
    public function binary(string $name, int $type = Binary::TYPE_GENERIC, ?string $className = null): self
    {
        return $this->add($name, TypeInterface::BINARY, $className);
    }

    /**
     * Autoconfigure fields mapping by parsing document class
     * Will declare all supported types declared on document properties
     *
     * @param class-string $documentClass
     *
     * @return $this
     */
    public function autoConfigure(string $documentClass): self
    {
        $this->parseClass(new ReflectionClass($documentClass));

        return $this;
    }

    /**
     * Build fields to get mapping
     *
     * @return FieldsMapping
     */
    public function build(): FieldsMapping
    {
        return new FieldsMapping($this->fields);
    }

    /**
     * Parse fields from a class using reflection
     *
     * @param ReflectionClass $class
     * @return void
     *
     * @throws \ReflectionException
     */
    private function parseClass(ReflectionClass $class): void
    {
        if (!$parser = $this->parser) {
            $this->parser = $parser = new ClassParser();
        }

        $this->fields = array_merge_recursive($this->fields, $parser->parse($class));
    }
}
