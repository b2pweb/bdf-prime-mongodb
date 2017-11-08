<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\Schema\IndexInterface;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Schema
 * @group Bdf_Prime_MongoDB_Schema_MongoIndex
 */
class MongoIndexTest extends TestCase
{
    /**
     *
     */
    public function test_name()
    {
        $index = new MongoIndex([
            'name' => 'idx_',
            'key'  => [
                'foo' => 1,
                'bar' => 1
            ]
        ]);

        $this->assertEquals('idx_', $index->name());
    }

    /**
     *
     */
    public function test_type_simple()
    {
        $index = new MongoIndex([
            'name' => 'idx_',
            'key'  => [
                'foo' => 1,
                'bar' => 1
            ]
        ]);

        $this->assertEquals(IndexInterface::TYPE_SIMPLE, $index->type());
        $this->assertFalse($index->unique());
        $this->assertFalse($index->primary());
    }

    /**
     *
     */
    public function test_type_primary()
    {
        $index = new MongoIndex([
            'name' => '_id_',
            'key'  => [
                '_id' => 1
            ]
        ]);

        $this->assertEquals(IndexInterface::TYPE_PRIMARY, $index->type());
        $this->assertTrue($index->unique());
        $this->assertTrue($index->primary());
    }

    /**
     *
     */
    public function test_type_unique()
    {
        $index = new MongoIndex([
            'name' => 'uniq_',
            'key'  => [
                'first_name' => 1,
                'last_name'  => 1
            ],
            'unique' => 1
        ]);

        $this->assertEquals(IndexInterface::TYPE_UNIQUE, $index->type());
        $this->assertTrue($index->unique());
        $this->assertFalse($index->primary());
    }

    /**
     *
     */
    public function test_fields()
    {
        $index = new MongoIndex([
            'name' => 'uniq_',
            'key'  => [
                'first_name' => 1,
                'last_name'  => 1
            ],
            'unique' => 1
        ]);

        $this->assertEquals(['first_name', 'last_name'], $index->fields());
    }
}