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
 * @group Bdf_Prime_MongoDB_Query_Command_Commands
 */
class CommandsTest extends TestCase
{
    /**
     *
     */
    public function test_create_with_string()
    {
        $this->assertEquals(
            new SimpleCommand('test'),
            Commands::create('test')
        );
    }

    /**
     *
     */
    public function test_create_with_string_and_argument()
    {
        $this->assertEquals(
            new SimpleCommand('test', ['foo' => 'bar']),
            Commands::create('test', ['foo' => 'bar'])
        );
    }

    /**
     *
     */
    public function test_create_with_array()
    {
        $this->assertEquals(
            new ArrayCommand(['foo' => 'bar']),
            Commands::create(['foo' => 'bar'])
        );
    }

    /**
     *
     */
    public function test_create_with_driver_command()
    {
        $cmd = new Command([]);

        $this->assertEquals(
            new DriverCommand($cmd),
            Commands::create($cmd)
        );
    }

    /**
     *
     */
    public function test_create_with_command()
    {
        $cmd = new Count('test');

        $this->assertSame($cmd, Commands::create($cmd));
    }
}
