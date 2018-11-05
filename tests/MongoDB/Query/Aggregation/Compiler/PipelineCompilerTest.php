<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Compiler;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Group;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Project;
use Bdf\Prime\MongoDB\Query\Command\Aggregate;
use Bdf\Prime\MongoDB\Query\Compiler\MongoCompiler;
use Bdf\Prime\Query\Contract\Whereable;
use MongoDB\BSON\UTCDateTime;

/**
 * Class PipelineCompilerTest
 */
class PipelineCompilerTest extends TestCase
{
    /**
     * @var PipelineCompiler
     */
    protected $compiler;

    /**
     * @var ConnectionManager
     */
    protected $manager;


    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->manager = new ConnectionManager([
            'dbConfig' => [
                'mongo' => [
                    'driver' => 'mongodb',
                    'host'   => '127.0.0.1',
                    'dbname' => 'TEST',
                ],
            ]
        ]);

        $this->manager->registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        $this->compiler = new PipelineCompiler(new MongoCompiler($this->manager->connection('mongo')));
    }

    /**
     *
     */
    public function test_compileAggregate()
    {
        $clause = $this->query();

        $clause
            ->match(function(Whereable $match) {
                $match->where('user.type', '=', 'bad_customer');
            })
            ->group('user.customer.id', function(Group $group) {
                $group->max('max_price', '$user.price');
            })
            ->project(function(Project $project) {
                $project->evaluate('max_price', '$mul', [2, '$max_price']);
            });

        $command = $this->compiler->compileAggregate($clause);

        $this->assertInstanceOf(Aggregate::class, $command);
        $this->assertEquals(
            [
                'aggregate' => 'users',
                'pipeline' => [
                    [
                        '$match' => [
                            'user.type' => 'bad_customer'
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => '$user.customer.id',
                            'max_price' => ['$max' => '$user.price']
                        ]
                    ],
                    [
                        '$project' => [
                            '_id' => false,
                            'max_price' => [
                                '$mul' => [2, '$max_price']
                            ]
                        ]
                    ]

                ]
            ],
            $command->document()
        );
    }


    /**
     *
     */
    public function test_group_null()
    {
        $this->assertEquals([
            '_id' => null
        ], $this->compiler->compileGroup(
            $this->query(),
            (new Group(null))->export()
        ));
    }

    /**
     *
     */
    public function test_group_null_with_accumulator()
    {
        $query = $this->query()->group(null, [
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
            $this->compiler->compileGroup(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_group_by_field()
    {
        $query = $this->query()->group('customer.id');
        $this->assertEquals([
                '_id' => '$customer.id'
            ],
            $this->compiler->compileGroup(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_group_by_field_and_accumulator()
    {
        $query = $this->query()
        ->group('customer.id', [
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
            $this->compiler->compileGroup(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_group_closure()
    {
        $query = $this->query()
            ->group('customer.id', function (Group $group) {
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
            $this->compiler->compileGroup(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_group_by_multiple_fields()
    {
        $query = $this->query()
            ->group([
                'customer' => 'customer.id',
                'name'
            ]);
        $this->assertEquals([
                '_id' => [
                    'customer' => '$customer.id',
                    'name' => '$name'
                ],
            ],
            $this->compiler->compileGroup(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_group_by_operation()
    {
        $query = $this->query()
            ->group([
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
            $this->compiler->compileGroup(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_match_with_3_parameters()
    {
        $query = $this->query()
            ->match('name', '>=', 'Pa');
        $this->assertEquals(
            [
                'name' => [
                    '$gte' => 'Pa'
                ]
            ],
            $this->compiler->compileMatch(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_match_with_2_parameters()
    {
        $query = $this->query()
            ->match('name', 'Paul');
        $this->assertEquals(
            [
                'name' => 'Paul'
            ],
            $this->compiler->compileMatch(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_match_with_array()
    {
        $query = $this->query()
            ->match(['name :gte' => 'Pa']);
        $this->assertEquals(
            [
                'name' => [
                    '$gte' => 'Pa'
                ]
            ],
            $this->compiler->compileMatch(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_match_with_closure()
    {
        $query = $this->query()
            ->match(function(Whereable $clause) {
                $clause->where('name', '>=', 'Pa');
                $clause->where('name', '>', 'Claude');
            });
        $this->assertEquals(
            [

                '$and' => [
                    [
                        'name' => [
                            '$gte' => 'Pa',
                        ],
                    ],
                    [
                        'name' => [
                            '$gt' => 'Claude'
                        ]
                    ]
                ]
            ],
            $this->compiler->compileMatch(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_match_with_nested_closure()
    {
        $query = $this->query()
            ->match(function(Whereable $clause) {
                $clause->where('name', '>=', 'Pa');
                $clause->where(function(Whereable $clause) {
                    $clause->where('id', '>', 10);
                });
            });
        $this->assertSame(
            [
                'name' => [
                    '$gte' => 'Pa',
                ],
                'id' => [
                    '$gt' => 10
                ]
            ],
            $this->compiler->compileMatch(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_match_with_closure_array()
    {
        $query = $this->query()
            ->match(function(Whereable $clause) {
                $clause->where([
                    'name >=' => 'Pa',
                    'name >'  => 'Claude'
                ]);
                $clause->orWhere([
                    'id >' => 5
                ]);
            });
        $this->assertSame(
            [
                '$or' => [
                    [
                        '$and' => [
                            [
                                'name' => [
                                    '$gte' => 'Pa',
                                ],
                            ],
                            [
                                'name' => [
                                    '$gt' => 'Claude'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => [
                            '$gt' => 5
                        ]
                    ]
                ]
            ],
            $this->compiler->compileMatch(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    public function test_project_with_closure()
    {
        $date = new \DateTime();
        $query = $this->query()
            ->project(function(Project $project) use($date) {
                $project->add('name', 'real_name');
                $project->evaluate('age', '$substract', [
                    $date, '$birthdate'
                ]);
            });

        $this->assertEquals(
            [
                'real_name' => '$name',
                'age' => [
                    '$substract' => [
                        new UTCDateTime($this->dateTimeToMilliseconds($date)),
                        '$birthdate'
                    ]
                ],
                '_id' => false
            ],
            $this->compiler->compileProject(
                $query,
                $query->statements['pipeline'][0]->export()
            )
        );
    }

    /**
     *
     */
    public function test_with_preconfigured_query()
    {
        $compiled = $this->compiler->compileAggregate(
            $this->manager->connection('mongo')
                ->from('users')
                ->select(['user.name', 'customer.id', 'orders'])
                ->where('date', '>', $now = new \DateTime())
                ->pipeline()
                ->group('customer.id', [
                    'names' => [
                        'push' => '$user.name'
                    ]
                ])
        );

        $this->assertInstanceOf(Aggregate::class, $compiled);
        $this->assertEquals([
            'aggregate' => 'users',
            'pipeline'  => [
                ['$project' => [
                    '_id'         => false,
                    'user.name'   => true,
                    'customer.id' => true,
                    'orders'      => true
                ]],
                ['$match' => [
                    'date' => [
                        '$gt' => new UTCDateTime($this->dateTimeToMilliseconds($now))
                    ]
                ]],
                ['$group' => [
                    '_id' => '$customer.id',
                    'names' => [
                        '$push' => '$user.name'
                    ]
                ]]
            ]
        ], $compiled->document());
    }

    /**
     * @return Pipeline
     */
    protected function query()
    {
        return new Pipeline(
            $this->manager->connection('mongo')->from('users')
        );
    }

    private function dateTimeToMilliseconds(\DateTime $dateTime)
    {
        $timestamp = (float) $dateTime->format('U.u');

        return (int) ($timestamp * 1000);
    }
}
