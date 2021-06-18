<?php

namespace Bdf\Prime\MongoDB\Platform\Types;

use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoDB\Platform\MongoPlatform;
use Bdf\Prime\Platform\Types\PlatformTypeInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\TypesRegistry;
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
     * @var BsonBinDataType
     */
    protected $type;

    /**
     * @var MongoPlatform
     */
    protected $platform;


    protected function setUp()
    {
        $this->platform = new MongoPlatform(new \Bdf\Prime\MongoDB\Driver\MongoPlatform(), new TypesRegistry());
        $this->type = new BsonBinDataType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals('binData', $this->type->declaration($column));
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
    public function test_fromDatabase_string()
    {
        $this->assertEquals('azerty', $this->type->fromDatabase('azerty'));
    }

    /**
     *
     */
    public function test_fromDatabase_Binary()
    {
        $this->assertEquals('azerty', $this->type->fromDatabase(new Binary('azerty', Binary::TYPE_GENERIC)));
    }

    /**
     *
     */
    public function test_toDatabase()
    {
        $this->assertEquals(new Binary('azerty', Binary::TYPE_GENERIC), $this->type->toDatabase('azerty'));
    }
}
