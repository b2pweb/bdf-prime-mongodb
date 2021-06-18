<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\Types\TypeInterface;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

/**
 * Class MongoInsertQueryTest
 */
class MongoInsertQueryTest extends TestCase
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
        $manager = new ConnectionManager(new ConnectionRegistry([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => '127.0.0.1',
                'dbname' => 'TEST',
            ],
        ]));
        ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

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
    public function test_insert_simple()
    {
        $this->assertEquals(1, $this->query()->values([
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1982-06-25'
        ])->execute());

        $this->assertEquals([
            [
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1982-06-25'
            ]
        ], $this->connection->builder()->from($this->collection)->all(['name', 'birth']));
    }

    /**
     *
     */
    public function test_insert_bulk()
    {
        $this->assertEquals(2, $this->query()
            ->bulk()
            ->values([
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1982-06-25'
            ])
            ->values([
                'name.first' => 'Mickey',
                'name.last'  => 'Mouse',
                'birth'      => '1924-11-28'
            ])
            ->execute()
        );

        $this->assertEquals([
            [
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1982-06-25'
            ],
            [
                'name.first' => 'Mickey',
                'name.last'  => 'Mouse',
                'birth'      => '1924-11-28'
            ],
        ], $this->connection->builder()->from($this->collection)->all(['name', 'birth']));
    }

    /**
     *
     */
    public function test_insert_bulk_values_replace()
    {
        $this->assertEquals(1, $this->query()
            ->bulk()
            ->values([
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1982-06-25'
            ], true)
            ->values([
                'name.first' => 'Mickey',
                'name.last'  => 'Mouse',
                'birth'      => '1924-11-28'
            ], true)
            ->execute()
        );

        $this->assertEquals([
            [
                'name.first' => 'Mickey',
                'name.last'  => 'Mouse',
                'birth'      => '1924-11-28'
            ],
        ], $this->connection->builder()->from($this->collection)->all(['name', 'birth']));
    }

    /**
     *
     */
    public function test_insert_columns()
    {
        $query = $this->query()->columns(['name.first', 'name.last', 'birth']);

        $this->assertEquals(1, $query
            ->values([
                'name.first' => 'John',
                'name.last'  => 'Doe',
            ])
            ->execute()
        );

        $this->assertEquals([
            [
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => null
            ],
        ], $this->connection->builder()->from($this->collection)->all(['name', 'birth']));

        $this->assertEquals(1, $query
            ->values([
                'name.first' => 'François',
                'name.last'  => 'Dupont',
                'birth'      => '1976-12-01',
                'other'      => 42
            ])
            ->execute()
        );

        $this->assertEquals([
            [
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => null
            ],
            [
                'name.first' => 'François',
                'name.last'  => 'Dupont',
                'birth'      => '1976-12-01',
            ]
        ], $this->connection->builder()->from($this->collection)->all(['name', 'birth']));
    }

    /**
     *
     */
    public function test_insert_columns_with_type()
    {
        $query = $this->query()->columns(['_id' => TypeInterface::GUID, 'value' => TypeInterface::BLOB]);

        $this->assertEquals(1, $query
            ->values([
                '_id'   => 1,
                'value' => 42
            ])
            ->execute()
        );

        $this->assertEquals([
            [
                '_id'   => new ObjectId('000000000000000000000001'),
                'value' => new Binary('42', Binary::TYPE_GENERIC)
            ],
        ], $this->connection->builder()->from($this->collection)->all());
    }

    /**
     *
     */
    public function test_replace_already_exists()
    {
        $this->connection->insert($this->collection, [
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1982-06-25'
        ]);

        $this->assertEquals(1, $this->query()
            ->replace()
            ->values([
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1986-02-25'
            ])
            ->execute()
        );

        $this->assertEquals([
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1986-02-25'
        ], $this->connection->builder()->from($this->collection)->where('_id', 1)->first());
    }

    /**
     *
     */
    public function test_replace_without_id()
    {
        $this->assertEquals(1, $this->query()
            ->replace()
            ->values([
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1986-02-25'
            ])
            ->execute()
        );

        $entity = $this->connection->builder()->from($this->collection)->where('name.first', 'John')->first();

        $this->assertInstanceOf(ObjectId::class, $entity['_id']);
        $this->assertEquals('John', $entity['name.first']);
        $this->assertEquals('Doe', $entity['name.last']);
        $this->assertEquals('1986-02-25', $entity['birth']);
    }

    /**
     *
     */
    public function test_replace_not_exists()
    {
        $this->connection->insert($this->collection, [
            '_id'        => 2,
            'name.first' => 'Alan',
            'name.last'  => 'Smith',
            'birth'      => '1945-03-30'
        ]);

        $this->assertEquals(1, $this->query()
            ->replace()
            ->values([
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1986-02-25'
            ])
            ->execute()
        );

        $this->assertEquals([
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1986-02-25'
        ], $this->connection->builder()->from($this->collection)->where('_id', 1)->first());
    }

    /**
     *
     */
    public function test_ignore_already_exists()
    {
        $this->connection->insert($this->collection, [
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1982-06-25'
        ]);

        $this->assertEquals(0, $this->query()
            ->ignore()
            ->values([
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1986-02-25'
            ])
            ->execute()
        );

        $this->assertEquals([
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1982-06-25'
        ], $this->connection->builder()->from($this->collection)->where('_id', 1)->first());
    }

    /**
     *
     */
    public function test_ignore_not_exists()
    {
        $this->assertEquals(1, $this->query()
            ->ignore()
            ->values([
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1986-02-25'
            ])
            ->execute()
        );

        $this->assertEquals([
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1986-02-25'
        ], $this->connection->builder()->from($this->collection)->where('_id', 1)->first());
    }

    /**
     *
     */
    public function test_insert_bulk_ignore()
    {
        $this->connection->insert($this->collection, [
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1982-04-25'
        ]);

        $this->assertEquals(1, $this->query()
            ->bulk()
            ->ignore()
            ->values([
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1982-06-25'
            ])
            ->values([
                '_id'        => 2,
                'name.first' => 'Mickey',
                'name.last'  => 'Mouse',
                'birth'      => '1924-11-28'
            ])
            ->execute()
        );

        $this->assertEquals([
            [
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1982-04-25'
            ],
            [
                '_id'        => 2,
                'name.first' => 'Mickey',
                'name.last'  => 'Mouse',
                'birth'      => '1924-11-28'
            ],
        ], $this->connection->builder()->from($this->collection)->all());
    }

    /**
     *
     */
    public function test_error_already_exists()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('dbal internal error has occurred');

        $this->connection->insert($this->collection, [
            '_id'        => 1,
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => '1982-06-25'
        ]);

        $this->assertEquals(1, $this->query()
            ->values([
                '_id'        => 1,
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth'      => '1986-02-25'
            ])
            ->execute()
        );
    }

    /**
     * @return MongoInsertQuery
     */
    private function query()
    {
        return $this->connection->make(MongoInsertQuery::class)->into($this->collection);
    }
}
