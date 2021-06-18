<?php

namespace Bdf\Prime\MongoDB\Schema;

use PHPUnit\Framework\TestCase;
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

    /**
     *
     */
    public function test_options()
    {
        $index = new MongoIndex([
            'name' => 'uniq_',
            'key'  => [
                'first_name' => 1,
                'last_name'  => 1
            ],
            'unique' => 1,
            'foo' => 'bar',
        ]);

        $this->assertEquals(['foo' => 'bar'], $index->options());
    }

    /**
     *
     */
    public function test_fieldOptions()
    {
        $index = new MongoIndex([
            'name' => 'uniq_',
            'key'  => [
                'first_name' => -1,
                'last_name'  => 1,
                'search' => 'text',
            ],
            'unique' => 1,
        ]);

        $this->assertEquals(['order' => 'DESC'], $index->fieldOptions('first_name'));
        $this->assertEquals([], $index->fieldOptions('last_name'));
        $this->assertEquals(['type' => 'text'], $index->fieldOptions('search'));
    }
}
