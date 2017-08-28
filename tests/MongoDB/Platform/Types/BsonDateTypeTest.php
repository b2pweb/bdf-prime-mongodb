<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Platform\MongoPlatform;
use Bdf\Prime\Platform\Types\PlatformTypeInterface;
use MongoDB\BSON\Binary;
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
     * @var PlatformTypeInterface
     */
    protected $type;

    /**
     * @var MongoPlatform
     */
    protected $platform;


    protected function setUp()
    {
        $this->type = new BsonDateType();
        $this->platform = new MongoPlatform(new \Bdf\Prime\MongoDB\Driver\MongoPlatform());
    }

    /**
     *
     */
    public function test_fromDatabase_null()
    {
        $this->assertNull($this->type->fromDatabase($this->platform, null));
    }

    /**
     *
     */
    public function test_toDatabase_null()
    {
        $this->assertNull($this->type->toDatabase($this->platform, null));
    }

    /**
     *
     */
    public function test_toDatabase()
    {
        $time = new \DateTime('2017-08-28 15:25:32');

        $this->assertInstanceOf(UTCDateTime::class, $this->type->toDatabase($this->platform, $time));
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
                $this->platform,
                $this->type->toDatabase($this->platform, $time)
            )
        );
    }
}
