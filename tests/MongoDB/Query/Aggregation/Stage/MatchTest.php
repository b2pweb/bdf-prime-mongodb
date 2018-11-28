<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;


use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use Bdf\Prime\Query\Contract\Whereable;
use PHPUnit\Framework\TestCase;

class MatchTest extends TestCase
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

        $this->grammar = new MongoGrammar($this->manager->connection('mongo')->platform());
    }

    /**
     *
     */
    public function test_compile_with_3_parameters()
    {
        $query = $this->query()->match('name', '>=', 'Pa');

        $this->assertEquals(
            [
                'name' => [
                    '$gte' => 'Pa'
                ]
            ],
            $query->statements['pipeline'][0]->compile($query, $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_with_2_parameters()
    {
        $query = $this->query()->match('name', 'Paul');

        $this->assertEquals(
            [
                'name' => 'Paul'
            ],
            $query->statements['pipeline'][0]->compile($query, $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_with_array()
    {
        $query = $this->query()
            ->match(['name :gte' => 'Pa']);
        $this->assertEquals(
            [
                'name' => [
                    '$gte' => 'Pa'
                ]
            ],
            $query->statements['pipeline'][0]->compile($query, $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_with_closure()
    {
        $query = $this->query()->match(function(Whereable $clause) {
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
            $query->statements['pipeline'][0]->compile($query, $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_with_nested_closure()
    {
        $query = $this->query()->match(function(Whereable $clause) {
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
            $query->statements['pipeline'][0]->compile($query, $this->grammar)
        );
    }

    /**
     *
     */
    public function test_compile_with_closure_array()
    {
        $query = $this->query()->match(function(Whereable $clause) {
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
            $query->statements['pipeline'][0]->compile($query, $this->grammar)
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
