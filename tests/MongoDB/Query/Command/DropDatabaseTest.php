<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use Bdf\PHPUnit\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_DropDatabase
 */
class DropDatabaseTest extends TestCase
{
    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new DropDatabase('my_collection');

        $this->assertEquals([
            'dropDatabase' => 1
        ], $cmd->document());
    }
}
