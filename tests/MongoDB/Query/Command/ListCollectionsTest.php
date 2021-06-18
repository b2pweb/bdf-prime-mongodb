<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use PHPUnit\Framework\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_ListCollection
 */
class ListCollectionsTest extends TestCase
{
    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new ListCollections();

        $this->assertEquals([
            'listCollections' => 1
        ], $cmd->document());
    }

    /**
     *
     */
    public function test_filter()
    {
        $cmd = new ListCollections();

        $this->assertEquals([
            'listCollections' => 1,
            'filter' => [
                'name' => ['$gt' => 'a']
            ]
        ], $cmd->filter(['name' => ['$gt' => 'a']])->document());
    }

    /**
     *
     */
    public function test_byName()
    {
        $cmd = new ListCollections();

        $this->assertEquals([
            'listCollections' => 1,
            'filter' => [
                'name' => 'my_collection'
            ]
        ], $cmd->byName('my_collection')->document());
    }
}
