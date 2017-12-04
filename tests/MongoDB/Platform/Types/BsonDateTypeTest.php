<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Platform\MongoPlatform;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\TypesRegistry;
use MongoDB\BSON\UTCDateTime;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Platform
 * @group Bdf_Prime_MongoDB_Platform_Types
 * @group Bdf_Prime_MongoDB_Platform_Types_BsonDateType
 */
class BsonDateTypeTest extends TestCase
{
    /**
     * @var BsonDateType
     */
    protected $type;

    /**
     * @var MongoPlatform
     */
    protected $platform;


    protected function setUp()
    {
        $this->platform = new MongoPlatform(new \Bdf\Prime\MongoDB\Driver\MongoPlatform(), new TypesRegistry());
        $this->type = new BsonDateType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals('date', $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_fromDatabase_null()
    {
        $this->assertNull($this->type->fromDatabase(null));
    }

    /**
     *
     */
    public function test_toDatabase_null()
    {
        $this->assertNull($this->type->toDatabase(null));
    }

    /**
     *
     */
    public function test_toDatabase()
    {
        $time = new \DateTime('2017-08-28 15:25:32');

        $this->assertInstanceOf(UTCDateTime::class, $this->type->toDatabase($time));
    }

    /**
     *
     */
    public function test_to_from_database()
    {
        $time = new \DateTime('2017-08-28 15:25:32');

        $this->assertEquals(
            $time,
            $this->type->fromDatabase(
                $this->type->toDatabase($time)
            )
        );
    }

    /**
     *
     */
    public function test_with_milliseconds()
    {
        $time = new \DateTime('2017-08-28 15:25:32.145');
        $db = $this->type->toDatabase($time);

        $this->assertEquals($time, $db->toDateTime());
        $this->assertEquals($time, $this->type->fromDatabase($db));
    }
}
