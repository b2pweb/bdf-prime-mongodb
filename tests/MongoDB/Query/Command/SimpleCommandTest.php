<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoAssertion;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_SimpleCommand
 */
class SimpleCommandTest extends TestCase
{
    use MongoAssertion;

    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new SimpleCommand('my_command', 'foo');

        $this->assertEquals('my_command', $cmd->name());
        $this->assertEquals([
            'my_command' => 'foo'
        ], $cmd->document());

        $this->assertCommand([
            'my_command' => 'foo',
        ], $cmd->get());
    }
}
