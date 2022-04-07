<?php

namespace MongoDB\Document\Mapping;

use Bdf\Prime\MongoDB\Document\Mapping\ClassParser;
use Bdf\Prime\MongoDB\Document\Mapping\Field;
use Bdf\Prime\MongoDB\TestDocument\BarDocument;
use DateTime;
use DateTimeInterface;
use MongoDB\Document\DocumentWithEmbedded;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClassParserTest extends TestCase
{
    public function test_parse_should_ignore_properties_without_types()
    {
        $doc = new class {
            public $foo;
            public string $bar;
            public $baz;
        };

        $parser = new ClassParser();
        $fields = $parser->parse(new ReflectionClass(get_class($doc)));

        $this->assertEquals(['bar' => new Field('bar', 'string', 'string')], $fields);
    }

    public function test_parse_should_ignore_properties_type_not_supported()
    {
        $doc = new class {
            public \Closure $foo;
        };

        $parser = new ClassParser();
        $this->assertEmpty($parser->parse(new ReflectionClass(get_class($doc))));
    }

    public function test_parse_mapped_types()
    {
        $doc = new class {
            public DateTimeInterface $foo;
            public string $bar;
            public int $baz;
            public bool $rab;
            public object $oof;
            public float $zab;
        };

        $parser = new ClassParser();
        $this->assertEquals([
            'foo' => new Field('foo', 'datetime', DateTimeInterface::class),
            'bar' => new Field('bar', 'string', 'string'),
            'baz' => new Field('baz', 'integer', 'int'),
            'rab' => new Field('rab', 'boolean', 'bool'),
            'oof' => new Field('oof', 'object', 'object'),
            'zab' => new Field('zab', 'double', 'float'),
        ], $parser->parse(new ReflectionClass(get_class($doc))));
    }

    public function test_parse_with_embedded()
    {
        $parser = new ClassParser();
        $fields = $parser->parse(new ReflectionClass(DocumentWithEmbedded::class));

        $this->assertEquals([
            'e1' => [
                'date' => new Field('date', 'datetime', DateTime::class),
                'other' => [
                    'a' => new Field('a', 'string', 'string'),
                    'b' => new Field('b', 'integer', 'int'),
                ],
            ],
            'e2' => [
                'date' => new Field('date', 'datetime', DateTime::class),
                'other' => [
                    'a' => new Field('a', 'string', 'string'),
                    'b' => new Field('b', 'integer', 'int'),
                ],
            ],
        ], $fields);
    }

    public function test_parse_with_different_visibility_and_ignore_static()
    {
        $doc = new class {
            public string $foo;
            protected string $bar;
            private string $baz;

            public static string $oof;
            protected static string $rab;
            private static string $zab;
        };

        $parser = new ClassParser();
        $this->assertEquals(['foo', 'bar', 'baz'], array_keys($parser->parse(new ReflectionClass(get_class($doc)))));
    }

    public function test_class_hierarchy()
    {
        $parser = new ClassParser();
        $this->assertEquals([
            '_type' => new Field('_type', 'string', 'string'),
            'bar' => new Field('bar', 'integer', 'int'),
            'value' => new Field('value', 'integer', 'int'),
        ], $parser->parse(new ReflectionClass(BarDocument::class)));
    }

    public function test_parse_with_dateTime_subclass()
    {
        $doc = new class {
            public MyCustomDate $foo;
        };

        $parser = new ClassParser();
        $this->assertEquals(['foo' => new Field('foo', 'datetime', MyCustomDate::class)], $parser->parse(new ReflectionClass(get_class($doc))));
    }
}

class MyCustomDate extends DateTime {}
