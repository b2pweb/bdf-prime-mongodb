<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoKeyValueQuery;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Types\TypeInterface;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;

/**
 * Class MongoKeyValueCompilerTest
 */
class MongoKeyValueCompilerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoKeyValueCompiler
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

        $this->compiler = new MongoKeyValueCompiler($this->connection);
    }

    /**
     *
     */
    public function test_select_all()
    {
        $this->assertEquals(new ReadQuery(
            'test',
            [],
            []
        ), $this->compiler->compileSelect($this->query()));
    }

    /**
     *
     */
    public function test_select_with_filters()
    {
        $this->assertEquals(new ReadQuery(
            'test',
            [
                'name'  => 'John',
                'birth' => new UTCDateTime((new \DateTime('1965-11-23'))->getTimestamp() * 1000),
            ],
            []
        ), $this->compiler->compileSelect($this->query()->where(['name' => 'John', 'birth' => new \DateTime('1965-11-23')])));
    }

    /**
     *
     */
    public function test_select_with_limit()
    {
        $this->assertEquals(new ReadQuery(
            'test',
            ['name'  => 'John'],
            [
                'limit' => 5
            ]
        ), $this->compiler->compileSelect($this->query()->where('name', 'John')->limit(5)));

        $this->assertEquals(new ReadQuery(
            'test',
            ['name'  => 'John'],
            [
                'limit' => 5,
                'skip'  => 3
            ]
        ), $this->compiler->compileSelect($this->query()->where('name', 'John')->limit(5, 3)));
    }

    /**
     *
     */
    public function test_select_with_project()
    {
        $this->assertEquals(new ReadQuery(
            'test',
            ['name'  => 'John'],
            [
                'projection' => [
                    'birth' => true,
                    '_id'   => false
                ]
            ]
        ), $this->compiler->compileSelect($this->query()->where('name', 'John')->project(['birth'])));

        $this->assertEquals(new ReadQuery(
            'test',
            ['name'  => 'John'],
            [
                'projection' => [
                    'birth' => true,
                    '_id'   => true
                ]
            ]
        ), $this->compiler->compileSelect($this->query()->where('name', 'John')->project(['birth', '_id'])));
    }

    /**
     *
     */
    public function test_compileCount()
    {
        $this->assertEquals(
            new Count('test'),
            $this->compiler->compileCount($this->query())
        );
    }

    /**
     *
     */
    public function test_compileCount_with_filters()
    {
        $this->assertEquals(
            (new Count('test'))
                ->query(['name' => 'John']),
            $this->compiler->compileCount($this->query()->where(['name' => 'John']))
        );
    }

    /**
     *
     */
    public function test_compileCount_with_limit()
    {
        $this->assertEquals(
            (new Count('test'))
                ->limit(5)
                ->skip(2)
            ,
            $this->compiler->compileCount($this->query()->limit(5)->offset(2))
        );
    }

    /**
     *
     */
    public function test_compileDelete_all()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->delete([]),
            $this->compiler->compileDelete($this->query())
        );
    }

    /**
     *
     */
    public function test_compileDelete_with_filters()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->delete(['name' => 'John']),
            $this->compiler->compileDelete($this->query()->where('name', 'John'))
        );
    }

    /**
     *
     */
    public function test_compileUpdate_all()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->update([],
                [
                    '$set' => [
                        'value' => 42,
                        'name'  => 'Bob',
                    ],
                ],
                ['multi' => true]
            ),
            $this->compiler->compileUpdate($this->query()->values(['value' => 42, 'name' => 'Bob']))
        );
    }

    /**
     *
     */
    public function test_compileUpdate_with_filter()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->update(
                ['name' => 'John'],
                [
                    '$set' => ['value' => 42],
                ],
                ['multi' => true]
            ),
            $this->compiler->compileUpdate($this->query()->where('name', 'John')->values(['value' => 42]))
        );
    }

    /**
     *
     */
    public function test_compileUpdate_with_type()
    {
        $this->assertEquals(
            (new WriteQuery('test'))->update(
                [],
                [
                    '$set' => ['value' => new Binary('42', Binary::TYPE_GENERIC)],
                ],
                ['multi' => true]
            ),
            $this->compiler->compileUpdate($this->query()->values(['value' => 42], ['value' => TypeInterface::BLOB]))
        );
    }

    /**
     * @return MongoKeyValueQuery
     */
    private function query()
    {
        return (new MongoKeyValueQuery($this->connection))->from('test');
    }
}
