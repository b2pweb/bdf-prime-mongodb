<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\Query\Expression\Like;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_MongoCompiler
 */
class MongoCompilerTest extends TestCase
{
    /**
     * @var MongoCompiler
     */
    protected $compiler;

    /**
     * @var MongoConnection
     */
    protected $connection;


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $manager = new ConnectionManager([
            'dbConfig' => [
                'mongo' => [
                    'driver' => 'mongodb',
                    'host'   => '127.0.0.1',
                    'dbname' => 'TEST',
                ],
            ]
        ]);
        $manager->registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        $this->connection = $manager->connection('mongo');

        $this->compiler = new MongoCompiler();

        $this->compiler->on($this->connection);
    }

    /**
     * @return MongoQuery
     */
    protected function query()
    {
        return $this->connection->from('test_collection');
    }

    /**
     *
     */
    public function test_compile_instances()
    {
        $this->assertInstanceOf(Query::class, $this->compiler->compileSelect($this->query()));
        $this->assertInstanceOf(BulkWrite::class, $this->compiler->compileInsert($this->query()->set('attr', 'values')));
        $this->assertInstanceOf(BulkWrite::class, $this->compiler->compileUpdate($this->query()));
        $this->assertInstanceOf(BulkWrite::class, $this->compiler->compileDelete($this->query()));
    }

    /**
     *
     */
    public function test_compileFilters_simple()
    {
        $query = $this->query()->where('first_name', 'John');

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            'first_name' => 'John'
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_type_transform()
    {
        $query = $this->query()->where('created_at', $date = new \DateTime('2017-07-10 15:45:32'));

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(UTCDateTime::class, $filters['created_at']);
        $this->assertEquals($date, $filters['created_at']->toDateTime());
    }

    /**
     *
     */
    public function test_compileFilters_multiple_and()
    {
        $query = $this->query()
            ->where('first_name', 'John')
            ->where('last_name', 'Doe')
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_unoptimisable_and()
    {
        $query = $this->query()
            ->where('age', '>=', 7)
            ->where('age', '<=', 77)
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            '$and' => [
                ['age' => ['$gte' => 7]],
                ['age' => ['$lte' => 77]],
            ]
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_with_or()
    {
        $query = $this->query()
            ->where('first_name', 'John')
            ->where('last_name', 'Doe')
            ->orWhere('age', '<', 30)
            ->where('attr', 25)
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            '$or' => [
                [
                    'first_name' => 'John',
                    'last_name'  => 'Doe',
                ],
                [
                    'age' => ['$lt' => 30],
                    'attr' => 25
                ]
            ]
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_nested()
    {
        $query = $this->query()
            ->where('first_name', 'John')
            ->orWhere(function (MongoQuery $query) {
                $query
                    ->where('age', 'between', [7, 77])
                    ->where('last_name', ':like', 'A%')
                ;
            })
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            '$or' => [
                ['first_name' => 'John'],
                [
                    '$and' => [
                        ['age' => ['$gte' => 7]],
                        ['age' => ['$lte' => 77]],
                    ],
                    'last_name' => ['$regex' => '^A.*$', '$options' => 'i']
                ]
            ]
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_transformer_expression_like()
    {
        $query = $this->query()
            ->where('first_name', (new Like('j'))->startsWith())
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            'first_name' => ['$regex' => '^j.*$', '$options' => 'i']
        ], $filters);
    }

    /**
     *
     */
    public function test_compileProjection()
    {
        $this->assertEquals([], $this->compiler->compileProjection([['column' => '*']]));

        $this->assertEquals([
            'attr1' => true,
            'attr2' => true,
            '_id'   => false
        ], $this->compiler->compileProjection([
            ['column' => 'attr1'],
            ['column' => 'attr2']
        ]));

        $this->assertEquals([
            'attr' => true,
            '_id'  => true
        ], $this->compiler->compileProjection([
            ['column' => 'attr'],
            ['column' => '_id']
        ]));
    }

    /**
     *
     */
    public function test_compileUpdateOperators()
    {
        $query = $this->query()
            ->set('attr', 5)
            ->inc('other', 2)
            ->mul('az', 3)
        ;

        $this->assertEquals([
            '$set' => [
                'attr' => 5,
            ],
            '$inc' => [
                'other' => 2
            ],
            '$mul' => [
                'az' => 3
            ]
        ], $this->compiler->compileUpdateOperators($query->statements));
    }

    /**
     *
     */
    public function test_compileSort()
    {
        $this->assertEquals([
            'attr' => -1,
            'other' => 1
        ], $this->compiler->compileSort([
            ['sort' => 'attr', 'order' => 'DESC'],
            ['sort' => 'other', 'order' => 'ASC'],
        ]));
    }
}
