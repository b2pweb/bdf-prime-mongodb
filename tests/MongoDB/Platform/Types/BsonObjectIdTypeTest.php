<?php

namespace Bdf\Prime\Types;

use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoDB\Platform\MongoPlatform;
use Bdf\Prime\MongoDB\Platform\Types\BsonObjectIdType;
use Bdf\Prime\Schema\ColumnInterface;
use MongoDB\BSON\ObjectID;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Platform
 * @group Bdf_Prime_MongoDB_Platform_Types
 * @group Bdf_Prime_MongoDB_Platform_Types_BsonObjectIdType
 */
class BsonObjectIdTypeTest extends TestCase
{

    /**
     * @var BsonObjectIdType
     */
    protected $type;

    /**
     * @var MongoPlatform
     */
    protected $platform;


    protected function setUp(): void
    {
        $this->platform = new MongoPlatform(new \Bdf\Prime\MongoDB\Driver\MongoPlatform(), new TypesRegistry());
        $this->type = new BsonObjectIdType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals('objectId', $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_toDatabase()
    {
        $id = $this->type->toDatabase('123');

        $this->assertInstanceOf(ObjectID::class, $id);
        $this->assertEquals(new ObjectID('000000000000000000000123'), $id);
    }

    /**
     *
     */
    public function test_fromDatabase()
    {
        $result = $this->type->fromDatabase(new ObjectID('000000000000000000000123'));

        $this->assertSame('123', $result);
    }
}
