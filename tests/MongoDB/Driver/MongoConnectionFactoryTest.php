<?php

namespace MongoDB\Driver;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ChainFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoConnectionFactory;
use PHPUnit\Framework\TestCase;

class MongoConnectionFactoryTest extends TestCase
{
    public function test_create()
    {
        $manager = new ConnectionManager(
            new ConnectionRegistry(
                [
                    'mongo' => [
                        'driver' => 'mongodb',
                        'host'   => $_ENV['MONGO_HOST'],
                        'dbname' => 'TEST',
                    ],
                ],
                new ChainFactory([
                    new MongoConnectionFactory(),
                ])
            )
        );

        $connection = $manager->getConnection('mongo');

        $this->assertInstanceOf(MongoConnection::class, $connection);
        $this->assertSame('mongo', $connection->getName());
        $this->assertSame([], $connection->schema()->getCollections());
    }

    public function test_create_with_mongo_driver()
    {
        $manager = new ConnectionManager(
            new ConnectionRegistry(
                [
                    'mongo' => [
                        'driver' => 'mongo',
                        'host'   => $_ENV['MONGO_HOST'],
                        'dbname' => 'TEST',
                    ],
                ],
                new ChainFactory([
                    new MongoConnectionFactory(),
                ])
            )
        );

        $connection = $manager->getConnection('mongo');

        $this->assertInstanceOf(MongoConnection::class, $connection);
        $this->assertSame('mongo', $connection->getName());
        $this->assertSame([], $connection->schema()->getCollections());
    }

    public function test_support()
    {
        $this->assertTrue((new MongoConnectionFactory())->support('mongo', ['driver' => 'mongodb']));
        $this->assertTrue((new MongoConnectionFactory())->support('mongo', ['driver' => 'mongo']));
        $this->assertFalse((new MongoConnectionFactory())->support('mongo', ['driver' => 'mysql']));
    }
}
