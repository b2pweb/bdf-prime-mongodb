<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\Query\Pagination\Walker;

/**
 * Class MongoKeyValueQueryTest
 */
class MongoKeyValueQueryTest extends TestCase
{
    /**
     * @var MongoConnection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $collection = 'person';

    /**
     * @var array
     */
    protected $data;


    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $manager = new ConnectionManager(new ConnectionRegistry([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => $_ENV['MONGO_HOST'],
                'dbname' => 'TEST',
            ],
        ]));
        ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        $this->connection = $manager->connection('mongo');

        $this->insertData();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->connection->dropDatabase();
    }

    /**
     *
     */
    protected function insertData()
    {
        $this->data = [
            [
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1985-10-22',
            ],
            [
                '_id'        => 2,
                'name.first' => 'François',
                'name.last'  => 'Dupont',
                'birth'      => '1974-04-13',
            ],
        ];

        foreach ($this->data as $data) {
            $this->connection->insert($this->collection, $data);
        }
    }

    /**
     *
     */
    public function test_all()
    {
        $this->assertEquals($this->data, $this->query()->all());
    }

    /**
     *
     */
    public function test_one_filter()
    {
        $this->assertEquals([$this->data[0]], $this->query()->where('name.first', 'John')->all());
    }

    /**
     *
     */
    public function test_array_filter()
    {
        $this->assertEquals(
            [$this->data[0]],
            $this->query()
                ->where([
                    'name.first' => 'John',
                    'name.last'  => 'Doe',
                ])
                ->all()
        );
    }

    /**
     *
     */
    public function test_filter_no_results()
    {
        $this->assertEmpty(
            $this->query()
                ->where('name.first', 'Not found')
                ->all()
        );
    }

    /**
     *
     */
    public function test_reuse()
    {
        $query = $this->query();

        $this->assertEquals($this->data[0], $query->where('name.first', 'John')->first());
        $this->assertEquals($this->data[1], $query->where('name.first', 'François')->first());
    }

    /**
     *
     */
    public function test_first()
    {
        $this->assertEquals($this->data[0], $this->query()->first());
    }

    /**
     *
     */
    public function test_limit()
    {
        $this->connection->insert($this->collection, $mickey = [
            '_id'        => 3,
            'name.first' => 'Mickey',
            'name.last'  => 'Mouse',
            'birth'      => '1928-11-18',
        ]);

        $this->connection->insert($this->collection, $donald = [
            '_id'        => 4,
            'name.first' => 'Donald',
            'name.last'  => 'Duck',
            'birth'      => '1934-06-09',
        ]);

        $query = $this->query();

        $this->assertEquals([$this->data[0], $this->data[1]], $query->limit(2)->all());
        $this->assertEquals([$mickey, $donald], $query->limit(2)->offset(2)->all());
    }

    /**
     *
     */
    public function test_walker()
    {
        $results = $this->query()->walk(1);

        $this->assertInstanceOf(Walker::class, $results);
        $this->assertEquals($this->data, iterator_to_array($results));
    }

    /**
     *
     */
    public function test_project()
    {
        $this->assertEquals([
            [
                '_id'   => 1,
                'birth' => '1985-10-22'
            ],
            [
                '_id'   => 2,
                'birth' => '1974-04-13'
            ]
        ],$this->query()->project(['_id', 'birth'])->all());
    }

    /**
     *
     */
    public function test_project_all()
    {
        $this->assertEquals($this->data,$this->query()->project('*')->all());
        $this->assertEquals($this->data,$this->query()->project(['_id', '*'])->all());
    }

    /**
     *
     */
    public function test_update_one()
    {
        $query = $this->query();

        $this->assertEquals(1, $query->where('name.first', 'John')->values(['birth' => '1985-10-21'])->update());
        $this->assertEquals('1985-10-21', $query->inRow('birth'));

        $this->assertEquals(1, $query->where('name.first', 'François')->values(['birth' => '1984-04-13'])->update());
        $this->assertEquals('1984-04-13', $query->inRow('birth'));
    }

    /**
     *
     */
    public function test_update_not_found()
    {
        $this->assertEquals(0, $this->query()->where('name.first', 'not found')->update(['name.last' => 'new name']));
        $this->assertEquals($this->data, $this->query()->all());
    }

    /**
     *
     */
    public function test_update_multiple()
    {
        $this->assertEquals(2, $this->query()->update(['name.last' => 'new name']));
        $this->assertEquals(['new name', 'new name'], $this->query()->inRows('name.last'));
    }

    /**
     *
     */
    public function test_delete_one()
    {
        $query = $this->query();

        $this->assertEquals(1, $query->where('name.first', 'John')->values(['birth' => '1985-10-21'])->delete());
        $this->assertNull($query->first());
        $this->assertCount(1, $this->query()->all());

        $this->assertEquals(1, $query->where('name.first', 'François')->values(['birth' => '1984-04-13'])->delete());
        $this->assertNull($query->first());
        $this->assertCount(0, $this->query()->all());
    }

    /**
     *
     */
    public function test_delete_not_found()
    {
        $this->assertEquals(0, $this->query()->where('name.first', 'not found')->delete(['name.last' => 'new name']));
        $this->assertEquals($this->data, $this->query()->all());
    }

    /**
     *
     */
    public function test_delete_multiple()
    {
        $this->assertEquals(2, $this->query()->delete());
        $this->assertEmpty($this->query()->all());
    }

    /**
     *
     */
    public function test_count()
    {
        $this->assertEquals(2, $this->query()->count());
        $this->assertEquals(1, $this->query()->where('name.first', 'John')->count());
        $this->assertEquals(0, $this->query()->where('name.first', 'Not found')->count());
        $this->assertEquals(1, $this->query()->limit(1)->count());
        $this->assertEquals(1, $this->query()->offset(1)->count());
    }

    /**
     *
     */
    public function test_paginationCount()
    {
        $this->assertEquals(2, $this->query()->paginationCount());
        $this->assertEquals(1, $this->query()->where('name.first', 'John')->paginationCount());
        $this->assertEquals(0, $this->query()->where('name.first', 'Not found')->paginationCount());
        $this->assertEquals(2, $this->query()->limit(1)->paginationCount());
        $this->assertEquals(2, $this->query()->offset(1)->paginationCount());
    }

    /**
     *
     */
    public function test_execute_error()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('dbal internal error has occurred');

        $this->query()->where('$$$$', '$$$$')->execute();
    }

    /**
     *
     */
    public function test_update_error()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('dbal internal error has occurred');

        $this->query()->where('$$$$', '$$$$')->update();
    }

    /**
     *
     */
    public function test_delete_error()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('dbal internal error has occurred');

        $this->query()->where('$$$$', '$$$$')->delete();
    }

    /**
     *
     */
    public function test_aggregates()
    {
        $this->connection->insert('aggregate', ['value' => 13]);
        $this->connection->insert('aggregate', ['value' => 42]);
        $this->connection->insert('aggregate', ['value' => 55]);
        $this->connection->insert('aggregate', ['value' => 22]);

        $this->assertEquals(13, $this->query()->from('aggregate')->min('value'));
        $this->assertEquals(55, $this->query()->from('aggregate')->max('value'));
        $this->assertEquals(132, $this->query()->from('aggregate')->sum('value'));
        $this->assertEquals(33, $this->query()->from('aggregate')->avg('value'));
        $this->assertEquals([13, 42, 55, 22], $this->query()->from('aggregate')->aggregate('push', 'value'));
    }

    /**
     *
     */
    public function test_collation()
    {
        $result = $this->query()
            ->select(['name'])
            ->collation(['locale' => 'fr', 'strength' => 1])
            ->where('name.first', 'john')
            ->first()
        ;

        $this->assertEquals(['name.first' => 'John', 'name.last' => 'Doe'], $result);
    }

    /**
     * @return MongoKeyValueQuery
     */
    private function query()
    {
        return $this->connection->make(MongoKeyValueQuery::class)->from($this->collection);
    }
}
