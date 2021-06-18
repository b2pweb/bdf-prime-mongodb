<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Aggregation
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage_Group
 */
class GroupTest extends TestCase
{
    /**
     * @var MongoGrammar
     */
    protected $grammar;

    /**
     * @var ConnectionManager
     */
    protected $manager;


    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->manager = new ConnectionManager(new ConnectionRegistry([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => '127.0.0.1',
                'dbname' => 'TEST',
            ],
        ]));

        ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        $this->grammar = new MongoGrammar($this->manager->connection('mongo')->platform());
    }

    /**
     *
     */
    public function test_constructor()
    {
        $group = new Group('field');

        $this->assertEquals(['_id' => 'field'], $group->export());
    }

    /**
     *
     */
    public function test_accumulator_with_field()
    {
        $group = new Group(null);

        $this->assertEquals(
            [
                '_id' => null,
                'aggr' => ['$avg' => '$field']
            ],
            $group->accumulator('aggr', '$avg', 'field')->export()
        );
    }

    /**
     *
     */
    public function test_accumulator_with_constant()
    {
        $group = new Group(null);

        $this->assertEquals(
            [
                '_id'   => null,
                'count' => ['$sum' => 1]
            ],
            $group->accumulator('count', '$sum', 1)->export()
        );
    }

    /**
     * @dataProvider provideMake
     */
    public function test_make($expected, $expression, $operations)
    {
        $this->assertEquals(
            $expected,
            Group::make($expression, $operations)->export()
        );
    }

    /**
     *
     */
    public function provideMake()
    {
        return [
            [['_id' => null], null, null],
            [['_id' => 'field'], 'field', null],
            [['_id' => null, 'agg' => ['$sum' => '$field']], null, ['agg' => ['sum' => 'field']]],
            [['_id' => null, 'agg' => ['$avg' => '$field']], null, ['agg' => ['avg' => 'field']]],
            [['_id' => null, 'agg' => ['$first' => '$field']], null, ['agg' => ['first' => 'field']]],
            [['_id' => null, 'agg' => ['$last' => '$field']], null, ['agg' => ['last' => 'field']]],
            [['_id' => null, 'agg' => ['$max' => '$field']], null, ['agg' => ['max' => 'field']]],
            [['_id' => null, 'agg' => ['$min' => '$field']], null, ['agg' => ['min' => 'field']]],
            [['_id' => null, 'agg' => ['$push' => '$field']], null, ['agg' => ['push' => 'field']]],
            [['_id' => null, 'agg' => ['$addToSet' => '$field']], null, ['agg' => ['addToSet' => 'field']]],
            [['_id' => null, 'agg' => ['$stdDevPop' => '$field']], null, ['agg' => ['stdDevPop' => 'field']]],
            [['_id' => null, 'agg' => ['$stdDevSamp' => '$field']], null, ['agg' => ['stdDevSamp' => 'field']]],
        ];
    }

    /**
     *
     */
    public function test_compile_null()
    {
        $this->assertEquals(['_id' => null], (new Group(null))->compile($this->query(), $this->grammar));
    }

    /**
     *
     */
    public function test_compile_null_with_accumulator()
    {
        $group = Group::make(null, [
            'orderCount' => [
                'sum' => [
                    '$size' => '$order'
                ]
            ]
        ]);

        $this->assertEquals([
            '_id'        => null,
            'orderCount' => [
                '$sum' => [
                    '$size' => '$order'
                ]
            ]
        ],
            $group->compile($this->query(), $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_by_field()
    {
        $group = Group::make('customer.id', []);
        $this->assertEquals(['_id' => '$customer.id'], $group->compile($this->query(), $this->grammar));
    }

    /**
     *
     */
    public function test_compile_by_field_and_accumulator()
    {
        $group = Group::make('customer.id', [
            'orderCount' => [
                'sum' => ['$size' => '$order']
            ]
        ]);
        $this->assertEquals([
            '_id'        => '$customer.id',
            'orderCount' => [
                '$sum' => [
                    '$size' => '$order'
                ]
            ]
        ],
            $group->compile($this->query(), $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_closure()
    {
        $group = Group::make('customer.id', function (Group $group) {
                $group->avg('pricePerArticle', [
                    '$divide' => [
                        ['$sum' => '$order.price'],
                        ['$sum' => '$order.count']
                    ]
                ]);
            });
        $this->assertEquals([
            '_id'   => '$customer.id',
            'pricePerArticle' => [
                '$avg' => [
                    '$divide' => [
                        ['$sum' => '$order.price'],
                        ['$sum' => '$order.count']
                    ]
                ]
            ],
        ],
            $group->compile($this->query(), $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_by_multiple_fields()
    {
        $group = Group::make([
            'customer' => 'customer.id',
            'name'
        ], null);
        $this->assertEquals([
            '_id' => [
                'customer' => '$customer.id',
                'name' => '$name'
            ],
        ],
            $group->compile($this->query(), $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_by_operation()
    {
        $group = Group::make([
            'nbOrderPair' => [
                '$mod' => [
                    ['$size' => '$order'],
                    2
                ]
            ]
        ], [
            'count' => ['sum' => 1]
        ]);
        $this->assertEquals([
            '_id'   => [
                'nbOrderPair' => [
                    '$mod' => [
                        ['$size' => '$order'],
                        2
                    ]
                ]
            ],
            'count' => ['$sum' => 1],
        ],
            $group->compile($this->query(), $this->grammar)
        );
    }

    /**
     * @return Pipeline
     */
    protected function query()
    {
        return (new Pipeline($this->manager->connection('mongo')))->from('users');
    }
}
