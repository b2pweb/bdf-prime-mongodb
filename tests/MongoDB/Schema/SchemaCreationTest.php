<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoAssertion;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Schema
 * @group Bdf_Prime_MongoDB_Schema_SchemaCreation
 */
class SchemaCreationTest extends TestCase
{
    use MongoAssertion;

    /**
     *
     */
    public function test_tables()
    {
        $tables = [
            new Table('table_', [], new IndexSet([
                new Index(['col_'], Index::TYPE_SIMPLE, 'name')
            ])),
            new Table('other_', [], new IndexSet([
                new Index(['attr_'], Index::TYPE_SIMPLE, 'idx')
            ]))
        ];

        $creation = new SchemaCreation($tables);

        $this->assertSame($tables, $creation->tables());
    }

    /**
     *
     */
    public function test_commands()
    {
        $tables = [
            new Table('table_', [], new IndexSet([
                new Index(['col_'], Index::TYPE_SIMPLE, 'name')
            ])),
            new Table('other_', [], new IndexSet([
                new Index(['attr_'], Index::TYPE_SIMPLE, 'idx')
            ]))
        ];

        $creation = new SchemaCreation($tables);

        $commands = $creation->commands();

        $this->assertCount(4, $commands);

        $this->assertCommand(['create' => 'table_'], $commands[0]);
        $this->assertCommand([
            'createIndexes' => 'table_',
            'indexes'       => [
                [
                    'key'  => ['col_' => 1],
                    'name' => 'name'
                ]
            ]
        ], $commands[1]);

        $this->assertCommand(['create' => 'other_'], $commands[2]);
        $this->assertCommand([
            'createIndexes' => 'other_',
            'indexes'       => [
                [
                    'key'  => ['attr_' => 1],
                    'name' => 'idx'
                ]
            ]
        ], $commands[3]);
    }
}
