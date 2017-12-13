<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use Bdf\PHPUnit\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_RenameCollection
 */
class RenameCollectionTest extends TestCase
{
    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new RenameCollection('my_collection', 'new_name');

        $this->assertEquals([
            'renameCollection' => 'my_collection',
            'to' => 'new_name',
            'dropTarget' => false
        ], $cmd->document());
    }

    /**
     *
     */
    public function test_dropTarget()
    {
        $cmd = new RenameCollection('my_collection', 'new_name');

        $this->assertEquals([
            'renameCollection' => 'my_collection',
            'to' => 'new_name',
            'dropTarget' => true
        ], $cmd->dropTarget()->document());
    }
}
