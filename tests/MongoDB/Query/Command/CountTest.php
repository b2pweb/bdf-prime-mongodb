<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoAssertion;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_Count
 */
class CountTest extends TestCase
{
    use MongoAssertion;

    /**
     *
     */
    public function test_defaults()
    {
        $cmd = new Count('my_collection');

        $this->assertEquals('count', $cmd->name());
        $this->assertEquals([
            'count' => 'my_collection'
        ], $cmd->document());

        $this->assertCommand([
            'count' => 'my_collection',
        ], $cmd->get());
    }

    /**
     *
     */
    public function test_with_query()
    {
        $cmd = new Count('my_collection');

        $this->assertSame($cmd, $cmd->query([
            'name' => ['$regex' => '^t']
        ]));

        $this->assertEquals([
            'count' => 'my_collection',
            'query' => ['name' => ['$regex' => '^t']]
        ], $cmd->document());
    }

    /**
     *
     */
    public function test_with_limit()
    {
        $cmd = new Count('my_collection');

        $this->assertSame($cmd, $cmd->limit(5));

        $this->assertEquals([
            'count' => 'my_collection',
            'limit' => 5
        ], $cmd->document());
    }

    /**
     *
     */
    public function test_with_skip()
    {
        $cmd = new Count('my_collection');

        $this->assertSame($cmd, $cmd->skip(5));

        $this->assertEquals([
            'count' => 'my_collection',
            'skip' => 5
        ], $cmd->document());
    }

    /**
     *
     */
    public function test_with_hint()
    {
        $cmd = new Count('my_collection');

        $this->assertSame($cmd, $cmd->hint(['name' => 1]));

        $this->assertEquals([
            'count' => 'my_collection',
            'hint' => ['name' => 1]
        ], $cmd->document());
    }

    /**
     *
     */
    public function test_with_readConcern()
    {
        $cmd = new Count('my_collection');

        $this->assertSame($cmd, $cmd->readConcern('majority'));

        $this->assertEquals([
            'count' => 'my_collection',
            'readConcern' => ['level' => 'majority']
        ], $cmd->document());
    }
}
