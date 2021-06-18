<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Aggregation
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage_Project
 */
class ProjectTest extends TestCase
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
    public function test_add_without_alias()
    {
        $project = new Project();

        $project->add('name');

        $this->assertEquals(
            [
                ['column' => 'name', 'alias' => null]
            ],
            $project->export()
        );
    }
    /**
     *
     */
    public function test_add_with_alias()
    {
        $project = new Project();

        $project->add('name', 'aliased_name');

        $this->assertEquals(
            [
                ['column' => 'name', 'alias' => 'aliased_name']
            ],
            $project->export()
        );
    }

    /**
     *
     */
    public function test_evaluate_all_parameters()
    {
        $project = new Project();

        $project->evaluate('articles', '$size', '$user.articles');

        $this->assertEquals(
            [
                ['column' => 'articles', 'expression' => ['$size' => '$user.articles']]
            ],
            $project->export()
        );
    }

    /**
     *
     */
    public function test_evaluate_multiple_expressions()
    {
        $project = new Project();

        $project->evaluate('articles', [
            'id'    => '$id',
            '$size' => '$user.articles'
        ]);

        $this->assertEquals(
            [
                [
                    'column' => 'articles',
                    'expression' => [
                        'id'    => '$id',
                        '$size' => '$user.articles'
                    ]
                ]
            ],
            $project->export()
        );
    }

    public function test_compile_with_closure()
    {
        $date = new \DateTime();
        $project = Project::make(function(Project $project) use($date) {
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
            $project->compile($this->query(), $this->grammar)
        );
    }

    /**
     * @return Pipeline
     */
    protected function query()
    {
        return (new Pipeline($this->manager->connection('mongo')))->from('users');
    }

    private function dateTimeToMilliseconds(\DateTime $dateTime)
    {
        $timestamp = (float) $dateTime->format('U.u');

        return (int) ($timestamp * 1000);
    }
}
