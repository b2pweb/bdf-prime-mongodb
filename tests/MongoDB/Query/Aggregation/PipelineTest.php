<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Group;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Project;
use Bdf\Prime\Query\Contract\Whereable;
use MongoDB\Driver\BulkWrite;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Aggregation
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Pipeline
 */
class PipelineTest extends TestCase
{
    /**
     * @var MongoConnection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var array
     */
    protected $data;


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $manager = new ConnectionManager(new ConnectionRegistry([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => '127.0.0.1',
                'dbname' => 'TEST',
            ],
        ]));
        ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        $this->connection = $manager->connection('mongo');

        $this->insertData();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->connection->dropDatabase();
    }

    /**
     *
     */
    public function test_no_operations()
    {
        $this->assertEquals(
            $this->data,
            $this->query()->execute()
        );
    }

    /**
     *
     */
    public function test_project_one_attribute()
    {
        $this->assertEqualsCanonicalizing(
            [
                ['name' => 'Paul'],
                ['name' => 'Jean'],
                ['name' => 'Claude'],
            ],
            $this->query()->project(['name'])->execute()
        );
    }

    /**
     *
     */
    public function test_project_with_alias()
    {
        $this->assertEqualsCanonicalizing(
            [
                ['name' => 'Paul',   'customer' => '123'],
                ['name' => 'Jean',   'customer' => '741'],
                ['name' => 'Claude', 'customer' => '741'],
            ],
            $this->query()->project(['name', 'customer' => 'customer.id'])->execute()
        );
    }

    /**
     *
     */
    public function test_project_with_evaluation()
    {
        $this->assertEqualsCanonicalizing(
            [
                ['name' => 'Paul',   'orderCount' => 2],
                ['name' => 'Jean',   'orderCount' => 3],
                ['name' => 'Claude', 'orderCount' => 1],
            ],
            $this->query()->project([
                'name',
                'orderCount' => [
                    '$size' => '$order'
                ]
            ])->execute()
        );
    }

    /**
     *
     */
    public function test_project_with_closure()
    {
        $this->assertEqualsCanonicalizing(
            [
                ['name' => 'Paul',   'customer' => '123', 'orderCount' => 2],
                ['name' => 'Jean',   'customer' => '741', 'orderCount' => 3],
                ['name' => 'Claude', 'customer' => '741', 'orderCount' => 1],
            ],
            $this->query()->project(function (Project $project) {
                $project
                    ->add('name')
                    ->add('customer.id', 'customer')
                    ->evaluate('orderCount', '$size', '$order')
                ;
            })->execute()
        );
    }

    /**
     *
     */
    public function test_group_null()
    {
        $this->assertEquals([
            ['_id' => null]
        ], $this->query()->group()->execute());
    }

    /**
     *
     */
    public function test_group_null_with_accumulator()
    {
        $this->assertEquals([
            [
                '_id'        => null,
                'orderCount' => 6
            ]
        ], $this->query()->group(null, [
            'orderCount' => [
                'sum' => [
                    '$size' => '$order'
                ]
            ]
        ])->execute());
    }

    /**
     *
     */
    public function test_group_emulate_count()
    {
        $this->assertEquals([
            [
                '_id'   => null,
                'count' => 3
            ]
        ], $this->query()->group(null, [
            'count' => ['sum' => 1]
        ])->execute());
    }

    /**
     *
     */
    public function test_group_by_field()
    {
        $this->assertEqualsCanonicalizing([
            ['_id'   => '741'],
            ['_id'   => '123'],
        ], $this->query()->group('customer.id')->execute());
    }

    /**
     *
     */
    public function test_group_by_field_and_accumulator()
    {
        $this->assertEqualsCanonicalizing([
            ['_id'   => '741', 'orderCount' => 4],
            ['_id'   => '123', 'orderCount' => 2],
        ], $this->query()
            ->group('customer.id', [
                'orderCount' => [
                    'sum' => ['$size' => '$order']
                ]
            ])
            ->execute()
        );
    }

    /**
     *
     */
    public function test_group_closure()
    {
        $this->assertEquals([
            ['_id'   => '741', 'pricePerArticle' => 4.78],
            ['_id'   => '123', 'pricePerArticle' => 11.09],
        ], $this->query()
            ->group('customer.id', function (Group $group) {
                $group->avg('pricePerArticle', [
                    '$divide' => [
                        ['$sum' => '$order.price'],
                        ['$sum' => '$order.count']
                    ]
                ]);
            })
            ->execute(),
            '', 0.001, 10, true
        );
    }

    /**
     *
     */
    public function test_group_by_multiple_fields()
    {
        $this->assertEquals([
            ['_id' => ['customer' => '741', 'name' => 'Jean']],
            ['_id' => ['customer' => '741', 'name' => 'Claude']],
            ['_id' => ['customer' => '123', 'name' => 'Paul']],
        ], $this->query()
            ->group([
                'customer' => 'customer.id',
                'name'
            ])
            ->execute(),
            '', 0, 10, true
        );
    }

    /**
     *
     */
    public function test_group_by_operation()
    {
        $this->assertEquals([
            ['_id' => ['nbOrderPair' => 0], 'count' => 1],
            ['_id' => ['nbOrderPair' => 1], 'count' => 2],
        ], $this->query()
            ->group([
                'nbOrderPair' => [
                    '$mod' => [
                        ['$size' => '$order'],
                        2
                    ]
                ]
            ], [
                'count' => ['sum' => 1]
            ])
            ->execute(),
            '', 0, 10, true
        );
    }

    /**
     *
     */
    public function test_match_with_3_parameters()
    {
        $this->assertEquals(
            [
                $this->data[0]
            ],
            $this->query()->match('name', '>=', 'Pa')->execute()
        );
    }

    /**
     *
     */
    public function test_match_with_2_parameters()
    {
        $this->assertEquals(
            [
                $this->data[0]
            ],
            $this->query()->match('name', 'Paul')->execute()
        );
    }

    /**
     *
     */
    public function test_match_with_array()
    {
        $this->assertEquals(
            [
                $this->data[0]
            ],
            $this->query()->match([
                'name :gte' => 'Pa'
            ])->execute()
        );
    }

    /**
     *
     */
    public function test_match_with_closure()
    {
        $this->assertEquals(
            [
                $this->data[0]
            ],
            $this->query()->match(function(Whereable $clause) {
                $clause->where('name', '>=', 'Pa');
                $clause->where('name', '>', 'Claude');
            })->execute()
        );
    }

    /**
     *
     */
    public function test_sort()
    {
        $this->assertEquals(
            [
                ['name' => 'Paul'],
                ['name' => 'Jean'],
                ['name' => 'Claude']
            ],
            $this->query()->project('name')->sort('name', 'DESC')->execute()
        );
    }

    /**
     *
     */
    public function test_sort_multiple()
    {
        $this->assertEquals(
            [
                ['name' => 'Claude'],
                ['name' => 'Jean'],
                ['name' => 'Paul'],
            ],
            $this->query()
                ->project('name')
                ->sort([
                    'customer.id' => 'DESC',
                    'name' => 'ASC'
                ])
                ->execute()
        );
    }

    /**
     *
     */
    public function test_sort_two_times()
    {
        $this->assertEquals(
            [
                ['name' => 'Claude'],
                ['name' => 'Jean'],
                ['name' => 'Paul'],
            ],
            $this->query()
                ->project('name')
                ->sort('customer.id', 'DESC')
                ->sort('name')
                ->execute()
        );
    }

    /**
     *
     */
    public function test_limit()
    {
        $this->assertEquals(
            [
                $this->data[0],
                $this->data[1]
            ],
            $this->query()
                ->limit(2)
                ->execute()
        );
    }

    /**
     *
     */
    public function test_limit_before_sort()
    {
        $this->assertEquals(
            [
                ['name' => 'Jean'],
                ['name' => 'Paul'],
            ],
            $this->query()
                ->project('name')
                ->limit(2)
                ->sort('name')
                ->execute()
        );
    }

    /**
     *
     */
    public function test_skip()
    {
        $this->assertEquals(
            [
                ['name' => 'Jean'],
                ['name' => 'Claude']
            ],
            $this->query()->project('name')->skip(1)->execute()
        );
    }

    /**
     *
     */
    public function test_skip_before_limit()
    {
        $this->assertEquals(
            [
                ['name' => 'Jean']
            ],
            $this->query()->project('name')->skip(1)->limit(1)->execute()
        );
    }

    /**
     *
     */
    public function test_skip_after_limit()
    {
        $this->assertEmpty(
            $this->query()->project('name')->limit(1)->skip(1)->execute()
        );
    }

    /**
     *
     */
    public function test_preconfigured_query()
    {
        $this->assertEquals(
            [
                [
                    'name' => 'Jean',
                    'customer' => [
                        'id' => '741'
                    ]
                ]
            ],
            $this->connection->from($this->collection)
                ->select(['name', 'customer.id'])
                ->where('customer.id', '>', '456')
                ->order('name', 'DESC')
                ->limit(1)
                ->pipeline()
                ->execute()
        );
    }

    /**
     *
     */
    protected function insertData()
    {
        $this->data = [
            [
                'name' => 'Paul',
                'customer' => [
                    'id' => '123'
                ],
                'order' => [
                    [
                        'price'   => 123.52,
                        'article' => 'sono',
                        'count'   => 2
                    ],
                    [
                        'price'   => 65,
                        'article' => 'poulet',
                        'count'   => 15
                    ],
                ]
            ],
            [
                'name' => 'Jean',
                'customer' => [
                    'id' => '741'
                ],
                'order' => [
                    [
                        'price'   => 35.14,
                        'article' => 'Foie gras',
                        'count'   => 4
                    ],
                    [
                        'price'   => 8.41,
                        'article' => 'poulet',
                        'count'   => 2
                    ],
                    [
                        'price'   => 15.50,
                        'article' => 'Sapin',
                        'count'   => 1
                    ],
                ]
            ],
            [
                'name' => 'Claude',
                'customer' => [
                    'id' => '741'
                ],
                'order' => [
                    [
                        'price'   => 36,
                        'article' => 'Yaourt',
                        'count'   => 32
                    ]
                ]
            ]
        ];

        $bulk = new BulkWrite();

        foreach ($this->data as &$data) {
            $id = $bulk->insert($data);
            $data['_id'] = $id;
        }

        $this->connection->executeWrite($this->collection, $bulk);
    }

    /**
     * @return Pipeline
     */
    protected function query()
    {
        return $this->connection->from($this->collection)->pipeline();
    }
}
