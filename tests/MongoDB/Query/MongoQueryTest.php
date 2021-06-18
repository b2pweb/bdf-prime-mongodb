<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\ObjectId;
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
        $manager = new ConnectionManager(new ConnectionRegistry([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => '127.0.0.1',
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
    public function test_count_with_criteria()
    {
        $this->assertSame(1, $this->query()->where('first_name', ':like', 'f%')->count());
    }

    /**
     *
     */
    public function test_count_with_limit()
    {
        $this->query()->insert(['first_name' => 'Paul']);
        $this->query()->insert(['first_name' => 'Claude']);

        $this->assertSame(2, $this->query()->limit(2)->count());
    }

    /**
     *
     */
    public function test_count_with_offset()
    {
        $this->query()->insert(['first_name' => 'Paul']);
        $this->query()->insert(['first_name' => 'Claude']);

        $this->assertSame(1, $this->query()->offset(3)->count());
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
        $this->assertEquals(1, $this->query()->insert([
            'first_name' => 'Paul',
            'last_name'  => 'Richard'
        ]));

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
        $this->assertEquals(1, $this->query()
            ->where('first_name', 'John')
            ->update([
                'age' => 35
            ])
        );

        $this->assertEquals(35, $this->query()->where('first_name', 'John')->inRow('age'));
    }

    /**
     *
     */
    public function test_delete()
    {
        $this->assertEquals(1, $this->query()
            ->where('first_name', 'John')
            ->delete()
        );

        $this->assertCount(1, $this->query()->all());
    }

    /**
     *
     */
    public function test_replace()
    {
        $data = $this->data[0];
        $data['first_name'] = 'new name';

        $this->assertEquals(1, $this->query()->replace($data));

        $this->assertEquals($data, $this->query()->where('_id', $data['_id'])->first());
    }

    /**
     *
     */
    public function test_replace_with_embedded()
    {
        $this->assertEquals(1, $this->query()->replace([
            '_id'        => 5,
            'name.first' => 'Paul',
            'name.last'  => 'Richard',
            'birth'      => '1965-11-05'
        ]));

        $this->assertEquals([
            '_id'        => 5,
            'name.first' => 'Paul',
            'name.last'  => 'Richard',
            'birth'      => '1965-11-05'
        ], $this->query()->where('_id', 5)->first());


        $this->assertCount(3, $this->query()->all());
    }

    /**
     *
     */
    public function test_replace_without_id()
    {
        $this->assertEquals(1, $this->query()->replace([
            'first_name' => 'Alan',
            'last_name'  => 'Smith',
        ]));

        $this->assertEquals([
            'first_name' => 'Alan',
            'last_name'  => 'Smith',
        ], $this->query()->where('first_name', 'Alan')->first(['first_name', 'last_name']));
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
     *
     */
    public function test_select_will_flatten_result()
    {
        $this->query()->insert([
            '_id'    => '123',
            'person' => [
                'name' => 'Michel Sardou',
                'age' => 70
            ],
            'job' => 'singer'
        ]);

        $this->assertEquals(
            [
                '_id'         => '123',
                'person.name' => 'Michel Sardou',
                'person.age'  => 70,
                'job'         => 'singer'
            ],
            $this->query()->where('_id', '123')->first()
        );
    }

    /**
     *
     */
    public function test_select_where_on_sub_document()
    {
        $this->query()->insert([
            '_id'    => '123',
            'person' => [
                'name' => 'Michel Sardou',
                'age' => 70
            ],
            'job' => 'singer'
        ]);

        $this->assertEquals(
            [
                '_id'         => '123',
                'person.name' => 'Michel Sardou',
                'person.age'  => 70,
                'job'         => 'singer'
            ],
            $this->query()->where('person.name', 'Michel Sardou')->first()
        );
    }

    /**
     *
     */
    public function test_like_empty()
    {
        $this->insertData();

        $this->assertEmpty($this->query()->where('first_name', ':like', '')->all());
    }

    /**
     *
     */
    public function test_like_no_joker()
    {
        $this->insertData();

        $this->assertEmpty($this->query()->where('first_name', ':like', 'i')->all());
    }

    /**
     *
     */
    public function test_like_case_insensitive()
    {
        $this->insertData();

        $this->assertEquals(
            [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            $this->query()->select(['first_name', 'last_name'])->where('first_name', ':like', 'j%')->first()
        );
    }

    /**
     *
     */
    public function test_raw()
    {
        $this->assertEquals(
            [
                [
                    'first_name' => 'François',
                    'last_name'  => 'Dupont'
                ]
            ],
            $this->query()->select(['first_name', 'last_name'])->whereRaw(['$where' => 'this.first_name.length > 5'])->all()
        );
    }

    /**
     *
     */
    public function test_sum()
    {
        // Clean collection
        $this->query()->delete();

        $this->query()->insert([
            'name' => 'Johnny Hallyday',
            'age'  => 74
        ]);

        $this->query()->insert([
            'name' => 'Charles Azanavour',
            'age'  => 93
        ]);

        $this->query()->insert([
            'name' => 'Michel Sardou',
            'age'  => 70
        ]);

        $this->query()->insert([
            'name' => 'Patrick Fiori',
            'age'  => 48
        ]);

        $this->query()->insert([
            'name' => 'Matt Pokora',
            'age'  => 32
        ]);

        $this->assertEquals(317, $this->query()->sum('age'));
    }

    /**
     *
     */
    public function test_max()
    {
        // Clean collection
        $this->query()->delete();

        $this->query()->insert([
            'name' => 'Johnny Hallyday',
            'age'  => 74
        ]);

        $this->query()->insert([
            'name' => 'Charles Azanavour',
            'age'  => 93
        ]);

        $this->query()->insert([
            'name' => 'Michel Sardou',
            'age'  => 70
        ]);

        $this->query()->insert([
            'name' => 'Patrick Fiori',
            'age'  => 48
        ]);

        $this->query()->insert([
            'name' => 'Matt Pokora',
            'age'  => 32
        ]);

        $this->assertEquals(93, $this->query()->max('age'));
    }

    /**
     *
     */
    public function test_min()
    {
        // Clean collection
        $this->query()->delete();

        $this->query()->insert([
            'name' => 'Johnny Hallyday',
            'age'  => 74
        ]);

        $this->query()->insert([
            'name' => 'Charles Azanavour',
            'age'  => 93
        ]);

        $this->query()->insert([
            'name' => 'Michel Sardou',
            'age'  => 70
        ]);

        $this->query()->insert([
            'name' => 'Patrick Fiori',
            'age'  => 48
        ]);

        $this->query()->insert([
            'name' => 'Matt Pokora',
            'age'  => 32
        ]);

        $this->assertEquals(32, $this->query()->min('age'));
    }

    /**
     *
     */
    public function test_avg()
    {
        // Clean collection
        $this->query()->delete();

        $this->query()->insert([
            'name' => 'Johnny Hallyday',
            'age'  => 74
        ]);

        $this->query()->insert([
            'name' => 'Charles Azanavour',
            'age'  => 93
        ]);

        $this->query()->insert([
            'name' => 'Michel Sardou',
            'age'  => 70
        ]);

        $this->query()->insert([
            'name' => 'Patrick Fiori',
            'age'  => 48
        ]);

        $this->query()->insert([
            'name' => 'Matt Pokora',
            'age'  => 32
        ]);

        $this->assertEquals(63.4, $this->query()->avg('age'));
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
