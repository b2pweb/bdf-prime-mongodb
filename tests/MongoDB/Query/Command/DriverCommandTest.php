<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use PHPUnit\Framework\TestCase;
use MongoDB\Driver\Command;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_DriverCommand
 */
class DriverCommandTest extends TestCase
{
    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new Command([]);

        $dcmd = new DriverCommand($cmd);

        $this->assertSame($cmd, $dcmd->get());
        $this->assertNull($dcmd->document());
        $this->assertNull($dcmd->name());
    }
}
