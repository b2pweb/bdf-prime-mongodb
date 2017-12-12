<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Test\Person;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
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
    use PrimeTestCase;

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
        $this->primeStart();

        Prime::service()->config()->getDbConfig()->merge([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => '127.0.0.1',
                'dbname' => 'TEST',
            ],
        ]);

        $this->connection = Prime::connection('mongo');

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
    public function test_compileFilters_with_array_value()
    {
        $query = $this->query()
            ->where('first_name', '~=', ['John', 'Paul', 'Richard'])
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            '$or' => [
                ['first_name' => ['$regex' => 'John']],
                ['first_name' => ['$regex' => 'Paul']],
                ['first_name' => ['$regex' => 'Richard']],
            ]
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_with_empty_array()
    {
        $query = $this->query()
            ->where('first_name', '>', [])
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            'first_name' => ['$gt' => null]
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_with_singleton_array()
    {
        $query = $this->query()
            ->where('age', '>', [5])
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            'age' => ['$gt' => 5]
        ], $filters);
    }

    /**
     *
     */
    public function test_compileFilters_with_raw()
    {
        $query = $this->query()
            ->whereRaw([
                '$where' => 'this.data.length > 15'
            ])
        ;

        $filters = $this->compiler->compileFilters(
            $query->statements['where']
        );

        $this->assertEquals([
            '$where' => 'this.data.length > 15'
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

    /**
     *
     */
    public function test_compileCount()
    {
        $query = $this->query()
            ->where('first_name', ':like', 't%')
            ->limit(5)
        ;

        $count = $this->compiler->compileCount($query);

        $this->assertInstanceOf(Count::class, $count);
        $this->assertEquals([
            'count' => 'test_collection',
            'query' => [
                'first_name' => [
                    '$regex' => '^t.*$',
                    '$options' => 'i'
                ]
            ],
            'limit' => 5
        ], $count->document());
    }

    /**
     *
     */
    public function test_compileExpression_scalar()
    {
        $this->assertSame(5, $this->compiler->compileExpression(5));
    }

    /**
     *
     */
    public function test_compileExpression_datetime()
    {
        $compiled = $this->compiler->compileExpression($date = new \DateTime('2017-10-12 15:32:12'));

        $this->assertInstanceOf(UTCDateTime::class, $compiled);
        $this->assertEquals($date, $compiled->toDatetime());
    }

    /**
     *
     */
    public function test_compileExpression_field_found()
    {
        $this->compiler->setPreprocessor(
            new OrmPreprocessor(Person::repository())
        );

        $this->assertEquals('$first_name', $this->compiler->compileExpression('$firstName'));
    }

    /**
     *
     */
    public function test_compileExpression_field_not_found()
    {
        $this->compiler->setPreprocessor(
            new OrmPreprocessor(Person::repository())
        );

        $this->assertEquals('$notFound', $this->compiler->compileExpression('$notFound'));
    }
}
