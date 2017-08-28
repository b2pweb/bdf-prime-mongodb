<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Platform\MongoPlatform;
use Bdf\Prime\Platform\Types\PlatformTypeInterface;
use MongoDB\BSON\Binary;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Platform
 * @group Bdf_Prime_MongoDB_Platform_Types
 * @group Bdf_Prime_MongoDB_Platform_Types_BsonBinDataType
 */
class BsonBinDataTypeTest extends TestCase
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
        $this->type = new BsonBinDataType();
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
    public function test_fromDatabase_string()
    {
        $this->assertEquals('azerty', $this->type->fromDatabase($this->platform, 'azerty'));
    }

    /**
     *
     */
    public function test_fromDatabase_Binary()
    {
        $this->assertEquals('azerty', $this->type->fromDatabase($this->platform, new Binary('azerty', Binary::TYPE_GENERIC)));
    }

    /**
     *
     */
    public function test_toDatabase()
    {
        $this->assertEquals(new Binary('azerty', Binary::TYPE_GENERIC), $this->type->toDatabase($this->platform, 'azerty'));
    }
}