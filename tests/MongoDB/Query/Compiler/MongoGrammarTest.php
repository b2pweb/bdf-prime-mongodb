<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\MongoDB\Test\Person;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Types\TypeInterface;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;

/**
 * Class MongoGrammarTest
 */
class MongoGrammarTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoGrammar
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

        Prime::service()->connections()->declareConnection('mongo', [
            'driver' => 'mongodb',
            'host'   => '127.0.0.1',
            'dbname' => 'TEST',
        ]);

        $this->connection = Prime::connection('mongo');

        $this->compiler = new MongoGrammar($this->connection->platform());
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
    public function test_filters_simple()
    {
        $query = $this->query()->where('first_name', 'John');

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            'first_name' => 'John'
        ], $filters);
    }

    /**
     *
     */
    public function test_filters_empty()
    {
        $query = $this->query();

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([], $filters);
    }

    /**
     *
     */
    public function test_filters_type_transform()
    {
        $query = $this->query()->where('created_at', $date = new \DateTime('2017-07-10 15:45:32'));

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(UTCDateTime::class, $filters['created_at']);
        $this->assertEquals($date, $filters['created_at']->toDateTime());
    }

    /**
     *
     */
    public function test_filters_multiple_and()
    {
        $query = $this->query()
            ->where('first_name', 'John')
            ->where('last_name', 'Doe')
        ;

        $filters = $this->compiler->filters(
            $query,
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
    public function test_filters_unoptimisable_and()
    {
        $query = $this->query()
            ->where('age', '>=', 7)
            ->where('age', '<=', 77)
        ;

        $filters = $this->compiler->filters(
            $query,
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
    public function test_filters_with_or()
    {
        $query = $this->query()
            ->where('first_name', 'John')
            ->where('last_name', 'Doe')
            ->orWhere('age', '<', 30)
            ->where('attr', 25)
        ;

        $filters = $this->compiler->filters(
            $query,
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
    public function test_filters_nested()
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

        $filters = $this->compiler->filters(
            $query,
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
    public function test_filters_transformer_expression_like()
    {
        $query = $this->query()
            ->where('first_name', (new Like('j'))->startsWith())
        ;

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            'first_name' => ['$regex' => '^j.*$', '$options' => 'i']
        ], $filters);
    }

    /**
     *
     */
    public function test_filters_transformer_expression_or_like()
    {
        $query = $this->query()
            ->where('first_name', (new Like(['j', 'f', 'k']))->startsWith())
        ;

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            '$or' => [
                ['first_name' => ['$regex' => '^j.*$', '$options' => 'i']],
                ['first_name' => ['$regex' => '^f.*$', '$options' => 'i']],
                ['first_name' => ['$regex' => '^k.*$', '$options' => 'i']],
            ]
        ], $filters);
    }

    /**
     *
     */
    public function test_filters_with_array_value()
    {
        $query = $this->query()
            ->where('first_name', '~=', ['John', 'Paul', 'Richard'])
        ;

        $filters = $this->compiler->filters(
            $query,
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
    public function test_filters_with_empty_array()
    {
        $query = $this->query()
            ->where('first_name', '>', [])
        ;

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            'first_name' => ['$gt' => null]
        ], $filters);
    }

    /**
     *
     */
    public function test_filters_with_singleton_array()
    {
        $query = $this->query()
            ->where('age', '>', [5])
        ;

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            'age' => ['$gt' => 5]
        ], $filters);
    }

    /**
     *
     */
    public function test_filters_with_raw()
    {
        $query = $this->query()
            ->whereRaw([
                '$where' => 'this.data.length > 15'
            ])
        ;

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            '$where' => 'this.data.length > 15'
        ], $filters);
    }

    /**
     * @dataProvider inOperators
     */
    public function test_filters_in($operator)
    {
        $query = $this->query()->where('name', $operator, ['bob', 'robert', 'will']);

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals(['name' => ['$in' => ['bob', 'robert', 'will']]], $filters);
    }

    public function inOperators()
    {
        return [
            ['in'], [':in'], ['='], [':eq']
        ];
    }

    /**
     *
     */
    public function test_filters_notin()
    {
        $query = $this->query()->where('name', '!in', ['bob', 'robert', 'will']);

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals(['name' => ['$nin' => ['bob', 'robert', 'will']]], $filters);
    }

    /**
     *
     */
    public function test_filters_notequals()
    {
        $query = $this->query()->where('name', '!=', 'bob');

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals(['name' => ['$ne' => 'bob']], $filters);
    }

    /**
     *
     */
    public function test_filters_not_between()
    {
        $query = $this->query()->where('age', '!between', [25, 45]);

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            '$or' => [
                ['age' => ['$lt' => 25]],
                ['age' => ['$gt' => 45]],
            ]
        ], $filters);
    }

    /**
     *
     */
    public function test_filters_undefined_operator()
    {
        $query = $this->query()->where('age', '$undefined', 42);

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            'age' => ['$undefined' => 42]
        ], $filters);
    }

    /**
     *
     */
    public function test_filters_elemMatch()
    {
        $query = $this->query()->where('person', '$elemMatch', ['firstName' => 'John', 'lastName' => 'Doe']);

        $filters = $this->compiler->filters(
            $query,
            $query->statements['where']
        );

        $this->assertEquals([
            'person' => ['$elemMatch' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ]],
        ], $filters);
    }

    /**
     *
     */
    public function test_projection()
    {
        $this->assertEquals([], $this->compiler->projection($this->query(), [['column' => '*']]));

        $this->assertEquals([
            'attr1' => true,
            'attr2' => true,
            '_id'   => false
        ], $this->compiler->projection($this->query(), [
            ['column' => 'attr1'],
            ['column' => 'attr2']
        ]));

        $this->assertEquals([
            'attr' => true,
            '_id'  => true
        ], $this->compiler->projection($this->query(), [
            ['column' => 'attr'],
            ['column' => '_id']
        ]));
    }

    /**
     *
     */
    public function test_projection_with_expression()
    {
        $this->assertEquals([
            'a'    => '$attr1',
            'name' => ['$concat' => ['$firstName', ' ', '$lastName']],
            '_id'  => false
        ], $this->compiler->projection($this->query(), [
            ['column' => 'attr1', 'alias' => 'a'],
            ['column' => 'name', 'expression' => ['$concat' => ['$firstName', ' ', '$lastName']]]
        ]));
    }

    /**
     *
     */
    public function test_sort()
    {
        $this->assertEquals([
            'attr' => -1,
            'other' => 1
        ], $this->compiler->sort($this->query(), [
            ['sort' => 'attr', 'order' => 'DESC'],
            ['sort' => 'other', 'order' => 'ASC'],
        ]));
    }

    /**
     *
     */
    public function test_expression_scalar()
    {
        $this->assertSame(5, $this->compiler->expression($this->query(), 5));
    }

    /**
     *
     */
    public function test_expression_datetime()
    {
        $compiled = $this->compiler->expression($this->query(), $date = new \DateTime('2017-10-12 15:32:12'));

        $this->assertInstanceOf(UTCDateTime::class, $compiled);
        $this->assertEquals($date, $compiled->toDatetime());
    }

    /**
     *
     */
    public function test_expression_field_found()
    {
        $query = Person::builder();

        $this->assertEquals('$first_name', $this->compiler->expression($query, '$firstName'));
    }

    /**
     *
     */
    public function test_expression_field_not_found()
    {
        $query = Person::builder();

        $this->assertEquals('$notFound', $this->compiler->expression($query, '$notFound'));
    }

    /**
     *
     */
    public function test_expression_nested()
    {
        $query = Person::builder();

        $this->assertEquals(
            [
                'name'    => ['$concat' => ['$first_name', ' ', '$last_name']],
                'surname' => '$last_name',
            ],
            $this->compiler->expression($query, [
                'name'    => ['$concat' => ['$firstName', ' ', '$lastName']],
                'surname' => '$lastName',
            ])
        );
    }

    /**
     *
     */
    public function test_set()
    {
        $this->assertEquals(
            ['$set' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]],
            $this->compiler->set(Person::builder(), [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ])
        );
    }

    /**
     *
     */
    public function test_set_with_explicit_type()
    {
        $this->assertEquals(
            ['$set' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'value'     => new Binary('42', Binary::TYPE_GENERIC)
            ]],
            $this->compiler->set(Person::builder(), [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'value' => 42
            ], ['value' => TypeInterface::BLOB])
        );
    }
}
