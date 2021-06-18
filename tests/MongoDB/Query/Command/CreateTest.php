<?php

namespace Bdf\Prime\MongoDB\Query\Command;


use PHPUnit\Framework\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_Create
 */
class CreateTest extends TestCase
{
    public function test_defaults()
    {
        $this->assertEquals(['create' => 'collection'], (new Create('collection'))->document());
    }

    public function test_validator()
    {
        $this->assertEquals([
            'create' => 'collection',
            'validator' => [
                'name' => ['$type' => 'string']
            ]
        ], (new Create('collection'))->validator(['name' => ['$type' => 'string']])->document());
    }
}
