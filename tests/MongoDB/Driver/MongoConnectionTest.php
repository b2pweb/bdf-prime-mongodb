<?php

namespace Bdf\Prime\MongoDB\Driver;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\MongoDB\Driver\Exception\MongoCommandException;
use Bdf\Prime\MongoDB\Driver\Exception\MongoDBALException;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Aggregation\PipelineInterface;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiler\MongoCompiler;
use Bdf\Prime\MongoDB\Query\MongoInsertQuery;
use Bdf\Prime\MongoDB\Query\MongoKeyValueQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\ReadPreference;

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
    public function test_connection()
    {
        $this->assertInstanceOf(MongoConnection::class, $this->connection);
        $this->assertInstanceOf(MongoDriver::class, $this->connection->getDriver());
        $this->assertInstanceOf(MongoPlatform::class, $this->connection->getDatabasePlatform());
    }

    /**
     *
     */
    public function test_with_replica()
    {
        $manager = new ConnectionManager(new ConnectionRegistry([
            'replica' => [
                'driver' => 'mongodb',
                'hosts'   => [
                    'mongo1.example.com:1234',
                    'mongo2.example.com:4567',
                ],
                'user' => 'my_user',
                'password' => 'my_password',
                'replicaSet' => 'my_set',
            ],
        ]));

        ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        /** @var MongoConnection $connection */
        $connection = $manager->getConnection('replica');
        $connection->connect();

        /** @var Manager $innerConnection */
        $innerConnection = ((array)$connection)["\0*\0_conn"];

        $this->assertInstanceOf(Manager::class, $innerConnection);
        $this->assertStringContainsString('mongodb://mongo1.example.com:1234,mongo2.example.com:4567', print_r($innerConnection, true));
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
    public function test_executeWrite_insert_select()
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
    public function test_insert_select()
    {
        $this->connection->insert($this->collection, [
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ]);

        $this->connection->insert($this->collection, [
            'first_name' => 'François',
            'last_name'  => 'Dupont'
        ]);

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

        $this->connection->executeWrite('test', $bulk);
    }

    /**
     *
     */
    public function test_factory()
    {
        $this->assertInstanceOf(MongoCompiler::class, $this->connection->factory()->compiler(MongoQuery::class));
        $this->assertSame($this->connection->factory()->compiler(MongoQuery::class), $this->connection->factory()->compiler(MongoQuery::class));
    }

    /**
     *
     */
    public function test_make()
    {
        $this->assertInstanceOf(MongoInsertQuery::class, $this->connection->make(InsertQueryInterface::class));
        $this->assertInstanceOf(MongoKeyValueQuery::class, $this->connection->make(KeyValueQueryInterface::class));
        $this->assertInstanceOf(Pipeline::class, $this->connection->make(PipelineInterface::class));
    }

    /**
     * @return void
     */
    public function test_exceptions()
    {
        $this->assertThrows(MongoDBALException::class, function () { $this->connection->execute($this->connection->builder()->from('not_found')->where('foo', '$invalid', 'bar')); });
        $this->assertThrows(MongoCommandException::class, function () { $this->connection->runCommand('invalid'); });
        $this->assertThrows(MongoCommandException::class, function () { $this->connection->runAdminCommand('invalid'); });
        $this->assertThrows(MongoDBALException::class, function () { (new WriteQuery('invalid'))->update(['foo' => ['$bar' => []]], [])->execute($this->connection); });
    }

    private function assertThrows(string $exceptionClass, callable $task): void
    {
        try {
            $task();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($exceptionClass, $e);
            return;
        }

        $this->fail('Expect ' . $exceptionClass . ' to be thrown');
    }
}
