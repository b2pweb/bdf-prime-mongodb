<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoAssertion;
use MongoDB\Driver\Command;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_ArrayCommand
 */
class ArrayCommandTest extends TestCase
{
    use MongoAssertion;

    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new ArrayCommand([
            'my_command' => 'arg',
            'foo' => 'bar'
        ]);

        $this->assertEquals('my_command', $cmd->name());
        $this->assertEquals([
            'my_command' => 'arg',
            'foo' => 'bar'
        ], $cmd->document());

        $this->assertCommand([
            'my_command' => 'arg',
            'foo' => 'bar'
        ], $cmd->get());
    }
}
