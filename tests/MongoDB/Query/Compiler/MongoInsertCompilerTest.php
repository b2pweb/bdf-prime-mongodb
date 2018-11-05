<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoInsertQuery;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;

/**
 * Class MongoInsertCompilerTest
 */
class MongoInsertCompilerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoInsertCompiler
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

        $this->compiler = new MongoInsertCompiler($this->connection);
    }

    /**
     *
     */
    public function test_compileInsert_simple()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->insert(['value' => 42, 'name' => 'John']),
            $this->compiler->compileInsert($this->query()->values(['value' => 42, 'name' => 'John']))
        );
    }

    /**
     *
     */
    public function test_compileInsert_embedded()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->insert([
                'value' => [
                    'int' => 42,
                    'string' => 'Forty-two',
                ],
                'key' => 'response'
            ]),
            $this->compiler->compileInsert($this->query()->values(['value.int' => 42, 'value.string' => 'Forty-two', 'key' => 'response']))
        );
    }

    /**
     *
     */
    public function test_compileInsert_bulk()
    {
        $this->assertEquals(
            (new WriteQuery('test'))
                ->insert(['value' => 42, 'name' => 'response'])
                ->insert(['value' => 666, 'name' => 'satan'])
            ,
            $this->compiler->compileInsert($this->query()
                ->bulk()
                ->values(['value' => 42, 'name' => 'response'])
                ->values(['value' => 666, 'name' => 'satan']))
        );
    }

    /**
     *
     */
    public function test_compileInsert_ignore()
    {
        $this->assertEquals(
            (new WriteQuery('test'))
                ->ordered(false)
                ->insert(['value' => 42, 'name' => 'response'])
            ,
            $this->compiler->compileInsert($this->query()
                ->ignore()
                ->values(['value' => 42, 'name' => 'response']))
        );
    }

    /**
     *
     */
    public function test_compileUpdate_replace_without_id()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->insert(['value' => 42, 'name' => 'response']),
            $this->compiler->compileUpdate($this->query()
                ->replace()
                ->values(['value' => 42, 'name' => 'response']))
        );
    }

    /**
     *
     */
    public function test_compileUpdate_replace_with_id()
    {
        $this->assertEquals(
            (new WriteQuery('test'))
                ->update(['_id' => 1], ['$set' => ['value' => 42, 'name' => 'response'], '$setOnInsert' => ['_id' => 1]], ['upsert' => true, 'multi' => false])
            ,
            $this->compiler->compileUpdate($this->query()
                ->replace()
                ->values(['_id' => 1, 'value' => 42, 'name' => 'response']))
        );
    }

    /**
     * @return MongoInsertQuery
     */
    private function query()
    {
        return (new MongoInsertQuery($this->connection))->into('test');
    }
}
