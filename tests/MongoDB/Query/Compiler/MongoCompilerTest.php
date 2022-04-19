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
use MongoDB\BSON\UTCDateTime;

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
    protected function setUp(): void
    {
        $this->primeStart();

        Prime::service()->connections()->declareConnection('mongo', [
            'driver' => 'mongodb',
            'host'   => $_ENV['MONGO_HOST'],
            'dbname' => 'TEST',
        ]);

        $this->connection = Prime::connection('mongo');

        $this->compiler = new MongoCompiler($this->connection);
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
        $this->assertInstanceOf(ReadQuery::class, $this->compiler->compileSelect($this->query()));
        $this->assertInstanceOf(WriteQuery::class, $this->compiler->compileInsert($this->query()->set('attr', 'values')));
        $this->assertInstanceOf(WriteQuery::class, $this->compiler->compileUpdate($this->query()->set('attr', 'value')));
        $this->assertInstanceOf(WriteQuery::class, $this->compiler->compileDelete($this->query()));
    }

    /**
     *
     */
    public function test_compileSelect()
    {
        $query = $this->query()
            ->where('first_name', 'John')
            ->where('last_name', 'like', '%D%')
            ->order('birth_date')
        ;

        $compiled = $this->compiler->compileSelect($query);

        $this->assertEquals(
            new ReadQuery(
                'test_collection',
                [
                    'first_name' => 'John',
                    'last_name' => ['$regex' => '^.*D.*$', '$options' => 'i']
                ],
                [
                    'sort' => ['birth_date' => 1]
                ]
            ),
            $compiled
        );
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
        ], $this->compiler->compileUpdateOperators($query, $query->statements));
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
    public function test_compileCount_with_collation()
    {
        $query = $this->query()
            ->collation(['locale' => 'fr', 'strength' => 2])
            ->where('first_name', ':like', 't%')
            ->limit(5)
        ;

        $count = $this->compiler->compileCount($query);

        $this->assertInstanceOf(Count::class, $count);
        $this->assertEquals([
            'collation' => ['locale' => 'fr', 'strength' => 2],
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
}
