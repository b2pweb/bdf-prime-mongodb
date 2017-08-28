<?php

namespace Bdf\Prime\MongoDB\Platform;


use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Platform\Types\BsonArrayType;
use Bdf\Prime\MongoDB\Platform\Types\BsonBinDataType;
use Bdf\Prime\MongoDB\Platform\Types\BsonBooleanType;
use Bdf\Prime\MongoDB\Platform\Types\BsonDateType;
use Bdf\Prime\MongoDB\Platform\Types\BsonDecimalType;
use Bdf\Prime\MongoDB\Platform\Types\BsonDoubleType;
use Bdf\Prime\MongoDB\Platform\Types\BsonIntegerType;
use Bdf\Prime\MongoDB\Platform\Types\BsonLongType;
use Bdf\Prime\MongoDB\Platform\Types\BsonObjectType;
use Bdf\Prime\MongoDB\Platform\Types\BsonStringType;
use Bdf\Prime\Platform\PlatformTypesRegistry;
use Bdf\Prime\Types\TypeInterface;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Platform
 * @group Bdf_Prime_MongoDB_Platform_MongoPlatform
 */
class MongoPlatformTest extends TestCase
{
    /**
     * @var MongoPlatform
     */
    protected $platform;


    /**
     *
     */
    protected function setUp()
    {
        $this->platform = new MongoPlatform(new \Bdf\Prime\MongoDB\Driver\MongoPlatform());
    }

    /**
     *
     */
    public function test_toDoctrinePlatform()
    {
        $this->assertInstanceOf(\Bdf\Prime\MongoDB\Driver\MongoPlatform::class, $this->platform->toDoctrinePlatform());
    }

    /**
     *
     */
    public function test_types()
    {
        /** @var PlatformTypesRegistry $types */
        $types = $this->platform->types();

        $this->assertInstanceOf(PlatformTypesRegistry::class, $types);

        $this->assertInstanceOf(BsonArrayType::class, $types->get(TypeInterface::TARRAY));
        $this->assertInstanceOf(BsonBinDataType::class, $types->get(TypeInterface::BLOB));
        $this->assertInstanceOf(BsonBooleanType::class, $types->get(TypeInterface::BOOLEAN));
        $this->assertInstanceOf(BsonDateType::class, $types->get(TypeInterface::DATETIME));
        $this->assertInstanceOf(BsonDecimalType::class, $types->get(TypeInterface::DECIMAL));
        $this->assertInstanceOf(BsonDoubleType::class, $types->get(TypeInterface::FLOAT));
        $this->assertInstanceOf(BsonIntegerType::class, $types->get(TypeInterface::INTEGER));
        $this->assertInstanceOf(BsonIntegerType::class, $types->get(TypeInterface::SMALLINT));
        $this->assertInstanceOf(BsonLongType::class, $types->get(TypeInterface::BIGINT));
        $this->assertInstanceOf(BsonObjectType::class, $types->get(TypeInterface::OBJECT));
        $this->assertInstanceOf(BsonStringType::class, $types->get(TypeInterface::STRING));
        $this->assertInstanceOf(BsonStringType::class, $types->get(TypeInterface::TEXT));
    }

    /**
     *
     */
    public function test_type_array()
    {
        $this->assertEquals(['Hello', 'World'], $this->platform->types()->get('array')->toDatabase($this->platform, ['Hello', 'World']));
        $this->assertEquals(['Hello', 'World'], $this->platform->types()->get('array')->fromDatabase($this->platform, ['Hello', 'World']));
    }

    /**
     *
     */
    public function test_type_object()
    {
        $this->assertEquals((object) ['Hello', 'World'], $this->platform->types()->get('object')->toDatabase($this->platform, ['Hello', 'World']));
        $this->assertEquals((object) ['Hello', 'World'], $this->platform->types()->get('object')->fromDatabase($this->platform, ['Hello', 'World']));
    }

    /**
     *
     */
    public function test_type_boolean()
    {
        $this->assertEquals(true, $this->platform->types()->get('boolean')->toDatabase($this->platform, true));
        $this->assertEquals(true, $this->platform->types()->get('boolean')->fromDatabase($this->platform, true));
    }

    /**
     *
     */
    public function test_type_integer()
    {
        $this->assertEquals(123, $this->platform->types()->get('integer')->toDatabase($this->platform, 123));
        $this->assertEquals(123, $this->platform->types()->get('integer')->fromDatabase($this->platform, 123));
    }

    /**
     *
     */
    public function test_type_float()
    {
        $this->assertEquals(12.3, $this->platform->types()->get('float')->toDatabase($this->platform, 12.3));
        $this->assertEquals(12.3, $this->platform->types()->get('float')->fromDatabase($this->platform, 12.3));
    }

    /**
     *
     */
    public function test_type_string()
    {
        $this->assertEquals('hello', $this->platform->types()->get('string')->toDatabase($this->platform, 'hello'));
        $this->assertEquals('hello', $this->platform->types()->get('string')->fromDatabase($this->platform, 'hello'));
    }
}
