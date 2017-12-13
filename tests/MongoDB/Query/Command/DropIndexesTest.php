<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use Bdf\PHPUnit\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_DropIndexes
 */
class DropIndexesTest extends TestCase
{
    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new DropIndexes('my_collection');

        $this->assertEquals([
            'dropIndexes' => 'my_collection',
            'index' => '*'
        ], $cmd->document());
    }

    /**
     *
     */
    public function test_index()
    {
        $cmd = new DropIndexes('my_collection');

        $this->assertEquals([
            'dropIndexes' => 'my_collection',
            'index' => 'idx_123'
        ], $cmd->index('idx_123')->document());
    }
}
