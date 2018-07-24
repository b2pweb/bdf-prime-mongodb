<?php

namespace Bdf\Prime\MongoDB\Driver;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Driver
 * @group Bdf_Prime_MongoDB_Driver_MongoConnection
 */
class MongoConnectionTest extends TestCase
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
    public function test_connection()
    {
        $this->assertInstanceOf(MongoConnection::class, $this->connection);
        $this->assertInstanceOf(MongoDriver::class, $this->connection->getDriver());
        $this->assertInstanceOf(MongoPlatform::class, $this->connection->getDatabasePlatform());
    }

    /**
     *
     */
    public function test_builder()
    {
        $this->assertInstanceOf(MongoQuery::class, $this->connection->builder());
    }

    /**
     *
     */
    public function test_from()
    {
        $this->assertInstanceOf(MongoQuery::class, $this->connection->from($this->collection));
    }

    /**
     *
     */
    public function test_insert_select()
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

        $result = $this->connection->executeSelect($this->collection, new Query([]));

        $array = $result->toArray();

        $this->assertCount(2, $array);

        $this->assertEquals('John', $array[0]['first_name']);
        $this->assertEquals('Doe',  $array[0]['last_name']);

        $this->assertEquals('François', $array[1]['first_name']);
        $this->assertEquals('Dupont',   $array[1]['last_name']);
    }

    /**
     *
     */
    public function test_transaction_rollback_emulation()
    {
        $bulk = new BulkWrite();
        $bulk->insert([
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ]);

        $this->connection->executeWrite($this->collection, $bulk);

        $this->assertFalse($this->connection->isTransactionActive());

        $this->connection->beginTransaction();
        {
            $this->assertTrue($this->connection->isTransactionActive());

            $bulk = new BulkWrite();
            $bulk->insert([
                'first_name' => 'François',
                'last_name'  => 'Dupont'
            ]);

            $this->connection->executeWrite($this->collection, $bulk);

            $result = $this->connection->executeSelect($this->collection, new Query([]))->toArray();
            $this->assertCount(2, $result);

            $this->connection->beginTransaction();
            {
                $bulk = new BulkWrite();
                $bulk->insert([
                    'first_name' => 'George',
                    'last_name'  => 'Dupont'
                ]);

                $this->connection->executeWrite($this->collection, $bulk);

                $result = $this->connection->executeSelect($this->collection, new Query([]))->toArray();
                $this->assertCount(3, $result);
            }
            $this->connection->rollBack();

            $result = $this->connection->executeSelect($this->collection, new Query([]))->toArray();
            $this->assertCount(2, $result);

            $this->assertEquals('John', $result[0]['first_name']);
            $this->assertEquals('Doe', $result[0]['last_name']);

            $this->assertEquals('François', $result[1]['first_name']);
            $this->assertEquals('Dupont', $result[1]['last_name']);

            $this->assertTrue($this->connection->isTransactionActive());
        }
        $this->connection->rollBack();

        $result = $this->connection->executeSelect($this->collection, new Query([]))->toArray();
        $this->assertCount(1, $result);

        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('Doe',  $result[0]['last_name']);
    }

    /**
     *
     */
    public function test_transaction_emulation_multiple_collections()
    {
        $bulk = new BulkWrite();
        $bulk->insert([
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ]);

        $this->connection->executeWrite($this->collection, $bulk);

        $bulk = new BulkWrite();
        $bulk->insert([
            'value' => 42
        ]);
        $this->connection->executeWrite('other', $bulk);

        $this->assertFalse($this->connection->isTransactionActive());

        $this->connection->beginTransaction();
        {
            $this->assertTrue($this->connection->isTransactionActive());

            $bulk = new BulkWrite();
            $bulk->insert([
                'first_name' => 'François',
                'last_name'  => 'Dupont'
            ]);

            $this->connection->executeWrite($this->collection, $bulk);

            $bulk = new BulkWrite();
            $bulk->insert([
                'value' => 43
            ]);
            $this->connection->executeWrite('other', $bulk);

            $this->assertCount(2, $this->connection->executeSelect($this->collection, new Query([]))->toArray());
            $this->assertCount(2, $this->connection->executeSelect('other', new Query([]))->toArray());
        }
        $this->connection->rollBack();

        $result = $this->connection->executeSelect($this->collection, new Query([]))->toArray();
        $this->assertCount(1, $result);

        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('Doe',  $result[0]['last_name']);

        $result = $this->connection->executeSelect('other', new Query([]))->toArray();
        $this->assertCount(1, $result);

        $this->assertEquals(42, $result[0]['value']);
    }

    /**
     *
     */
    public function test_transaction_commit_emulation()
    {
        $bulk = new BulkWrite();
        $bulk->insert([
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ]);

        $this->connection->executeWrite($this->collection, $bulk);

        $bulk = new BulkWrite();
        $bulk->insert([
            'value' => 42
        ]);
        $this->connection->executeWrite('other', $bulk);


        $this->assertFalse($this->connection->isTransactionActive());

        $this->connection->beginTransaction();
        {
            $this->assertTrue($this->connection->isTransactionActive());

            $bulk = new BulkWrite();
            $bulk->insert([
                'first_name' => 'François',
                'last_name'  => 'Dupont'
            ]);

            $this->connection->executeWrite($this->collection, $bulk);

        }
        $this->connection->commit();

        $result = $this->connection->executeSelect($this->collection, new Query([]))->toArray();
        $this->assertCount(2, $result);

        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('Doe', $result[0]['last_name']);

        $this->assertEquals('François', $result[1]['first_name']);
        $this->assertEquals('Dupont', $result[1]['last_name']);

        $this->assertCount(1, $this->connection->executeSelect('other', new Query([]))->toArray());

        $this->assertFalse($this->connection->isTransactionActive());
    }

    /**
     *
     */
    public function test_select_error()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('MongoDB : unknown operator: $bad_operator');

        $this->connection->executeSelect('test', new Query(['field' => ['$bad_operator' => 5]]));
    }

    /**
     *
     */
    public function test_write_error()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('MongoDB : Unknown modifier: $invalid');

        $bulk = new BulkWrite();
        $bulk->update(
            [],
            [
                '$invalid' => 44
            ]
        );

        var_dump($this->connection->executeWrite('test', $bulk));
    }
}
