<?php

namespace Bdf\Prime\MongoDB\Query\Compiled;


use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use Bdf\Prime\MongoDB\Driver\ResultSet\WriteResultSet;
use MongoDB\Driver\BulkWrite;

class WriteQueryTest extends TestCase
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
    public function test_execute()
    {
        $query = new WriteQuery($this->collection);

        $query
            ->insert([
                '_id'   => 1,
                'name'  => 'response',
                'value' => 42,
            ])
            ->insert([
                '_id'   => 2,
                'name'  => 'satan',
                'value' => 666,
            ])
            ->update(['_id' => 1], ['$set' => ['name' => 'The response']])
            ->delete(['_id' => 2])
        ;

        $this->assertEquals(4, $query->count());

        $result = $query->execute($this->connection);

        $this->assertInstanceOf(WriteResultSet::class, $result);
        $this->assertEquals(4, $result->count());

        $this->assertEquals([[
            '_id'   => 1,
            'name'  => 'The response',
            'value' => 42,
        ]], $this->connection->from($this->collection)->all());
    }

    /**
     *
     */
    public function test_merge()
    {
        $query = new WriteQuery($this->collection);

        $query->insert([
            '_id'   => 1,
            'name'  => 'response',
            'value' => 42,
        ]);

        $query->merge(
            (new WriteQuery($this->collection))
                ->insert([
                    '_id'   => 2,
                    'name'  => 'satan',
                    'value' => 666,
                ])
        );

        $this->assertEquals(2, $query->count());

        $result = $query->execute($this->connection);

        $this->assertInstanceOf(WriteResultSet::class, $result);
        $this->assertEquals(2, $result->count());

        $this->assertEquals([
            [
                '_id'   => 1,
                'name'  => 'response',
                'value' => 42,
            ],
            [
                '_id'   => 2,
                'name'  => 'satan',
                'value' => 666,
            ]
        ], $this->connection->from($this->collection)->all());
    }
}