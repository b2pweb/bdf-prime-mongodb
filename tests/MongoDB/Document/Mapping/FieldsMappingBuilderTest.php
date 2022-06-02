<?php

namespace MongoDB\Document\Mapping;

use Bdf\Prime\MongoDB\Document\Mapping\Field;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMapping;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMappingBuilder;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClass;
use PHPUnit\Framework\TestCase;

class FieldsMappingBuilderTest extends TestCase
{
    public function test_autoConfigure()
    {
        $builder = new FieldsMappingBuilder();
        $builder->autoConfigure(DocumentWithoutBaseClass::class);

        $this->assertEquals(new FieldsMapping([
            'value' => new Field('value', 'string', 'string'),
        ]), $builder->build());
    }

    public function test_add()
    {
        $builder = new FieldsMappingBuilder();

        $builder->add('foo', 'integer');
        $builder->add('bar', 'string');

        $this->assertEquals(new FieldsMapping([
            'foo' => new Field('foo', 'integer', null),
            'bar' => new Field('bar', 'string', null),
        ]), $builder->build());
    }

    public function test_dateTime()
    {
        $builder = new FieldsMappingBuilder();

        $builder->dateTime('foo', \DateTimeImmutable::class);

        $this->assertEquals(new FieldsMapping([
            'foo' => new Field('foo', 'datetime', \DateTimeImmutable::class),
        ]), $builder->build());
    }

    public function test_binary()
    {
        $builder = new FieldsMappingBuilder();

        $builder->binary('foo');

        $this->assertEquals(new FieldsMapping([
            'foo' => new Field('foo', 'binary', null),
        ]), $builder->build());
    }
}
