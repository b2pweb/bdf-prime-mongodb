<?php

namespace Bdf\Prime\MongoDB\Query\Compiled;


use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use MongoDB\Driver\BulkWrite;

class ReadQueryTest extends TestCase
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

        $this->connection = $manager->getConnection('mongo');
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
    public function test_execute()
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

        $query = new ReadQuery($this->collection, ['first_name' => 'John'], ['projection' => ['_id' => false]]);

        $result = $query->execute($this->connection);

        $this->assertInstanceOf(CursorResultSet::class, $result);
        $this->assertEquals([[
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ]], $result->all());
    }
}
