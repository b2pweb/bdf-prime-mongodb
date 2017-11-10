<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use MongoDB\Driver\BulkWrite;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_MongoQuery
 */
class MongoQueryTest extends TestCase
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

        $this->insertData();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
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
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            [
                'first_name' => 'François',
                'last_name'  => 'Dupont'
            ]
        ];

        $bulk = new BulkWrite();

        foreach ($this->data as &$data) {
            $id = $bulk->insert($data);
            $data['_id'] = $id;
        }

        $this->connection->executeWrite($this->collection, $bulk);
    }

    /**
     * @return MongoQuery
     */
    protected function query()
    {
        return $this->connection->from($this->collection);
    }

    /**
     *
     */
    public function test_count()
    {
        $this->assertSame(2, $this->query()->count());
        $this->assertSame(2, $this->query()->paginationCount());
    }

    /**
     *
     */
    public function test_all()
    {
        $result = $this->query()->all();

        $this->assertCount(2, $result);
        $this->assertEquals($this->data, $result);
    }

    /**
     *
     */
    public function test_all_column()
    {
        $result = $this->query()->all('first_name');

        $this->assertEquals([
            ['first_name' => 'John'],
            ['first_name' => 'François']
        ], $result);
    }

    /**
     *
     */
    public function test_insert()
    {
        $this->query()->insert([
            'first_name' => 'Paul',
            'last_name'  => 'Richard'
        ]);

        $this->assertCount(3, $this->query()->all());
    }

    /**
     *
     */
    public function test_simple_where_functional()
    {
        $result = $this->query()->where('first_name', 'John')->all();

        $this->assertCount(1, $result);
        $this->assertEquals($this->data[0], $result[0]);
    }

    /**
     *
     */
    public function test_multiple_where_functional()
    {
        $this->query()->insert([
            'first_name' => 'Jean',
            'last_name'  => 'Arnaud'
        ]);

        $result = $this->query()
            ->where('first_name', 'John')
            ->orWhere(function (MongoQuery $query) {
                $query->where('last_name', ':like', '%o%');
            })
            ->all();

        $this->assertCount(2, $result);
        $this->assertEquals($this->data, $result);
    }

    /**
     *
     */
    public function test_update()
    {
        $this->query()
            ->where('first_name', 'John')
            ->update([
                'age' => 35
            ])
        ;

        $this->assertEquals(35, $this->query()->where('first_name', 'John')->inRow('age'));
    }

    /**
     *
     */
    public function test_delete()
    {
        $this->query()
            ->where('first_name', 'John')
            ->delete()
        ;

        $this->assertCount(1, $this->query()->all());
    }

    /**
     *
     */
    public function test_replace()
    {
        $this->query()
            ->where('first_name', 'John')
            ->replace([
                'name' => 'John Doe'
            ])
        ;

        $this->assertNull($this->query()->where('first_name', 'John')->first());
        $this->assertEquals(['name' => 'John Doe'], $this->query()->where('name', ':like', 'John%')->first('name'));
    }

    /**
     *
     */
    public function test_order()
    {
        $results = $this->query()
            ->order('first_name')
            ->inRows('first_name')
        ;

        $this->assertEquals(['François', 'John'], $results);
    }

    /**
     *
     */
    public function test_limit()
    {
        $results = $this->query()->limit(1)->all();
        $this->assertCount(1, $results);
    }

    /**
     *
     */
    public function test_iterator()
    {
        $count = 0;

        foreach ($this->query()->walk(1) as $row) {
            ++$count;
        }

        $this->assertEquals(2, $count);
    }

    /**
     * @dataProvider operatorsDataProvider
     */
    public function test_operators($expect, $field, $operator, $value)
    {
        // Clean collection
        $this->query()->delete();

        $this->query()->insert([
            'name' => 'Jean Arnaud',
            'age' => 52
        ]);

        $this->query()->insert([
            'name' => 'John Doe',
            'age' => 36
        ]);

        $this->query()->insert([
            'name' => 'Michel Sardou',
            'age' => 70
        ]);

        $this->assertEquals($expect, $this->query()->where($field, $operator, $value)->inRows('name'));
    }

    /**
     * @return array
     */
    public function operatorsDataProvider()
    {
        return [
            [['Jean Arnaud', 'John Doe'], 'age', 'between', [10, 60]],
            [['Michel Sardou'], 'age', '!between', [10, 60]],
            [['Jean Arnaud', 'Michel Sardou'], 'age', ':in', [52, 70]],
            [['John Doe'], 'age', '!in', [52, 70]],
            [['Jean Arnaud', 'Michel Sardou'], 'age', '>', 50],
            [['Jean Arnaud', 'John Doe'], 'name', ':regex', '^\\w{4,4}\\s\\w+$'],
            [['John Doe', 'Michel Sardou'], 'name', ':like', '%o%'],
            [['Jean Arnaud', 'Michel Sardou'], 'age', '!=', 36],
            [['Jean Arnaud', 'John Doe', 'Michel Sardou'], 'age', '$exists', true],
        ];
    }
}
