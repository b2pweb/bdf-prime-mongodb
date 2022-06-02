<?php

namespace MongoDB\Document\Mapping;

use Bdf\Prime\MongoDB\Document\Mapping\Field;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMapping;
use Bdf\Prime\MongoDB\Platform\Types\BsonIntegerType;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Types\TypesRegistryInterface;
use DateTimeImmutable;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

class FieldsMappingTest extends TestCase
{
    use PrimeTestCase;

    private TypesRegistryInterface $types;

    protected function setUp(): void
    {
        $this->primeStart();

        Prime::service()->connections()->declareConnection('mongo', [
            'driver' => 'mongodb',
            'host'   => $_ENV['MONGO_HOST'],
            'dbname' => 'TEST',
        ]);

        $this->types = Prime::connection('mongo')->platform()->types();
    }

    public function test_fromDatabase()
    {
        $mapping = new FieldsMapping([
            'a' => [
                'b' => new Field('b', 'integer', 'int'),
                'c' => new Field('c', 'datetime', \DateTimeImmutable::class),
            ],
            'd' => new Field('d', 'double', 'float'),
        ]);

        $this->assertSame([], $mapping->fromDatabase([], $this->types));
        $this->assertSame(['foo' => 15], $mapping->fromDatabase(['foo' => 15], $this->types));
        $this->assertSame(['a' => ['b' => 14]], $mapping->fromDatabase(['a' => ['b' => '14']], $this->types));
        $this->assertEquals([
            'a' => [
                'b' => 14,
                'c' => new DateTimeImmutable('2015-02-30'),
            ],
            'd' => 14.5,
        ], $mapping->fromDatabase([
            'a' => [
                'b' => 14,
                'c' => new UTCDateTime(new DateTimeImmutable('2015-02-30')),
            ],
            'd' => 14.5,
        ], $this->types));
    }

    public function test_toDatabase()
    {
        $mapping = new FieldsMapping([
            'a' => [
                'b' => new Field('b', 'integer', 'int'),
                'c' => new Field('c', 'datetime', \DateTimeImmutable::class),
            ],
            'd' => new Field('d', 'double', 'float'),
        ]);

        $this->assertSame([], $mapping->toDatabase([], $this->types));
        $this->assertSame(['foo' => 15], $mapping->toDatabase(['foo' => 15], $this->types));
        $this->assertSame(['a' => ['b' => 14]], $mapping->toDatabase(['a' => ['b' => '14']], $this->types));
        $this->assertEquals([
            'a' => [
                'b' => 14,
                'c' => new UTCDateTime(new DateTimeImmutable('2015-02-30')),
            ],
            'd' => 14.5,
        ], $mapping->toDatabase([
            'a' => [
                'b' => 14,
                'c' => new DateTimeImmutable('2015-02-30'),
            ],
            'd' => 14.5,
        ], $this->types));
    }

    public function test_typeOf()
    {
        $mapping = new FieldsMapping([
            'a' => [
                'b' => new Field('b', 'integer', 'int'),
                'c' => new Field('c', 'datetime', \DateTimeImmutable::class),
            ],
            'd' => new Field('d', 'double', 'float'),
        ]);

        $this->assertNull($mapping->typeOf('not.found', $this->types));
        $this->assertNull($mapping->typeOf('a', $this->types));
        $this->assertInstanceOf(BsonIntegerType::class, $mapping->typeOf('a.b', $this->types));
    }
}
