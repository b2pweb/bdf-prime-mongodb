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
use Bdf\Prime\MongoDB\Query\Command\Aggregate;
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
    public function setUp(): void
    {
        $this->manager = new ConnectionManager(new ConnectionRegistry([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => $_ENV['MONGO_HOST'],
                'dbname' => 'TEST',
            ],
        ]));

        ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        $this->compiler = new PipelineCompiler($this->manager->getConnection('mongo'));
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
                ],
                'cursor' => new \stdClass(),
            ],
            $command->document()
        );
    }

    /**
     *
     */
    public function test_with_preconfigured_query()
    {
        $compiled = $this->compiler->compileAggregate(
            $this->manager->getConnection('mongo')
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
            ],
            'cursor' => new \stdClass(),
        ], $compiled->document());
    }

    /**
     * @return Pipeline
     */
    protected function query()
    {
        return (new Pipeline($this->manager->getConnection('mongo')))->from('users');
    }

    private function dateTimeToMilliseconds(\DateTime $dateTime)
    {
        $timestamp = (float) $dateTime->format('U.u');

        return (int) ($timestamp * 1000);
    }
}
