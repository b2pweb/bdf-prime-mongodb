<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoAssertion;
use Bdf\Prime\MongoDB\Query\Command\CreateIndexes;
use Bdf\Prime\MongoDB\Query\Command\DropIndexes;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Comparator\IndexSetComparator;
use Bdf\Prime\Schema\Comparator\ReplaceIndexSetComparator;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Schema
 * @group Bdf_Prime_MongoDB_Schema_IndexSetDiff
 */
class IndexSetDiffTest extends TestCase
{
    use MongoAssertion;

    /**
     *
     */
    public function test_for_creation()
    {
        $comparator = new IndexSetCreationComparator(new IndexSet([
            new Index(['col_' => []], Index::TYPE_SIMPLE, 'col'),
            new Index(['first_name' => [], 'last_name' => []], Index::TYPE_UNIQUE, 'name'),
        ]));

        $diff = new IndexSetDiff('my_collection', $comparator);

        $this->assertSame('my_collection', $diff->collection());
        $this->assertInstanceOf(ReplaceIndexSetComparator::class, $diff->comparator());

        $commands = $diff->commands();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(CreateIndexes::class, $commands[0]);

        $this->assertEquals([
            'createIndexes' => 'my_collection',
            'indexes' => [
                [
                    'key' => ['col_' => 1],
                    'name' => 'col'
                ],
                [
                    'key' => [
                        'first_name' => 1,
                        'last_name'  => 1
                    ],
                    'name' => 'name',
                    'unique' => 1
                ]
            ]
        ], $commands[0]->document());
    }

    /**
     *
     */
    public function test_for_deletion()
    {
        $comparator = new IndexSetComparator(
            new IndexSet([
                new Index(['col_' => []], Index::TYPE_SIMPLE, 'col'),
                new Index(['first_name' => [], 'last_name' => []], Index::TYPE_UNIQUE, 'name'),
            ]),
            new IndexSet([])
        );

        $diff = new IndexSetDiff('my_collection', $comparator);

        $this->assertSame('my_collection', $diff->collection());
        $this->assertInstanceOf(ReplaceIndexSetComparator::class, $diff->comparator());

        $commands = $diff->commands();

        $this->assertCount(2, $commands);
        $this->assertContainsOnly(DropIndexes::class, $commands);

        $this->assertEquals([
            'dropIndexes' => 'my_collection',
            'index'       => 'col'
        ], $commands[0]->document());

        $this->assertEquals([
            'dropIndexes' => 'my_collection',
            'index'       => 'name'
        ], $commands[1]->document());
    }

    /**
     *
     */
    public function test_for_change()
    {
        $comparator = new IndexSetComparator(
            new IndexSet([
                new Index(['col_' => []], Index::TYPE_SIMPLE, 'col'),
                new Index(['first_name' => [], 'last_name' => []], Index::TYPE_SIMPLE, 'name'),
            ]),
            new IndexSet([
                new Index(['col_' => []], Index::TYPE_SIMPLE, 'col'),
                new Index(['first_name' => [], 'last_name' => []], Index::TYPE_UNIQUE, 'name'),
            ])
        );

        $diff = new IndexSetDiff('my_collection', $comparator);

        $this->assertSame('my_collection', $diff->collection());
        $this->assertInstanceOf(ReplaceIndexSetComparator::class, $diff->comparator());

        $commands = $diff->commands();

        $this->assertCount(2, $commands);

        $this->assertInstanceOf(DropIndexes::class, $commands[0]);
        $this->assertEquals([
            'dropIndexes' => 'my_collection',
            'index'       => 'name'
        ], $commands[0]->document());

        $this->assertInstanceOf(CreateIndexes::class, $commands[1]);
        $this->assertEquals([
            'createIndexes' => 'my_collection',
            'indexes'       => [
                [
                    'key' => [
                        'first_name' => 1,
                        'last_name'  => 1
                    ],
                    'name' => 'name',
                    'unique' => 1
                ]
            ]
        ], $commands[1]->document());
    }

    /**
     *
     */
    public function test_primary_will_be_skiped()
    {
        $comparator = new IndexSetComparator(
            new IndexSet([
                new Index(['_id' => []], Index::TYPE_PRIMARY, '_id_'),
            ]),
            new IndexSet([])
        );

        $diff = new IndexSetDiff('my_collection', $comparator);

        $this->assertSame('my_collection', $diff->collection());
        $this->assertInstanceOf(ReplaceIndexSetComparator::class, $diff->comparator());

        $commands = $diff->commands();

        $this->assertEmpty($commands);
    }

    /**
     *
     */
    public function test_mixed()
    {
        $comparator = new IndexSetComparator(
            new IndexSet([
                new Index(['col_' => []], Index::TYPE_SIMPLE, 'col'),
                new Index(['deprecated_' => []], Index::TYPE_SIMPLE, 'to_delete'),
                new Index(['first_name' => [], 'last_name' => []], Index::TYPE_SIMPLE, 'name'),
            ]),
            new IndexSet([
                new Index(['col_' => []], Index::TYPE_SIMPLE, 'col'),
                new Index(['first_name' => [], 'last_name' => []], Index::TYPE_UNIQUE, 'name'),
                new Index(['added_' => []], Index::TYPE_SIMPLE, 'to_add'),
            ])
        );

        $diff = new IndexSetDiff('my_collection', $comparator);

        $this->assertSame('my_collection', $diff->collection());
        $this->assertInstanceOf(ReplaceIndexSetComparator::class, $diff->comparator());

        $commands = $diff->commands();

        $this->assertCount(3, $commands);

        $this->assertEquals([
            'dropIndexes' => 'my_collection',
            'index'       => 'to_delete'
        ], $commands[0]->document());

        $this->assertEquals([
            'dropIndexes' => 'my_collection',
            'index'       => 'name'
        ], $commands[1]->document());

        $this->assertEquals([
            'createIndexes' => 'my_collection',
            'indexes'       => [
                [
                    'key'  => ['added_' => 1],
                    'name' => 'to_add'
                ],
                [
                    'key' => [
                        'first_name' => 1,
                        'last_name'  => 1
                    ],
                    'name' => 'name',
                    'unique' => 1
                ]
            ]
        ], $commands[2]->document());
    }
}
