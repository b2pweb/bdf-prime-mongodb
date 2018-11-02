<?php

namespace Bdf\Prime\MongoDB\Query\Command;


use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use MongoDB\Driver\BulkWrite;

class FunctionalTest extends TestCase
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
    public function test_count()
    {
        $bulk = new BulkWrite();

        $bulk->insert([
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ]);

        $bulk->insert([
            'first_name' => 'François',
            'last_name'  => 'Dupont'
        ]);

        $this->connection->executeWrite($this->collection, $bulk);

        $this->assertEquals([['n' => 2, 'ok' => 1]], (new Count($this->collection))->execute($this->connection)->all());
    }

    /**
     *
     */
    public function test_collection()
    {
        $this->assertEquals([['ok' => 1]], (new Create('new-collection'))->execute($this->connection)->all());
        $this->assertEquals('new-collection', (new ListCollections())->execute($this->connection)->all()[0]['name']);

        $this->assertEquals([[
            'ok' => 1,
            'ns' => 'TEST.new-collection',
            'nIndexesWas' => 1
        ]], (new Drop('new-collection'))->execute($this->connection)->all());
        $this->assertEmpty((new ListCollections())->execute($this->connection)->all());
    }
}
