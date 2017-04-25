<?php

namespace Bdf\Prime\MongoDB;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Doctrine\DBAL\Schema\Index;

/**
 * @group Bdf_
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_MongoSchemaManager
 */
class MongoSchemaManagerTest extends TestCase
{
    /**
     * @var MongoSchemaManager
     */
    protected $schema;

    /**
     * @var MongoConnection
     */
    protected $connection;


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

        $this->connection = $manager->connection('mongo');

        $this->schema = new MongoSchemaManager($this->connection);
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
    public function test_loadTable()
    {
        $this->connection->runCommand('create', 'test_collection');
        $this->connection->runCommand([
            'createIndexes' => 'test_collection',
            'indexes'       => [
                [
                    'key' => [
                        'first_name' => 1,
                        'last_name'  => 1
                    ],
                    'name' => 'search_name',
                    'unique' => true
                ],
                [
                    'key' => [
                        'age' => -1
                    ],
                    'name' => 'age_order'
                ]
            ]
        ]);

        $table = $this->schema->loadTable('test_collection');

        $this->assertEquals('test_collection', $table->getName());
        $this->assertEquals(new Index('search_name', ['first_name', 'last_name'], true), $table->getIndex('search_name'));
        $this->assertEquals(new Index('age_order', ['age']), $table->getIndex('age_order'));
        $this->assertEquals(new Index('_id_', ['_id'], false, true), $table->getIndex('_id_'));
        $this->assertEquals(['_id'], $table->getPrimaryKeyColumns());
    }

    /**
     *
     */
    public function test_hasDatabase()
    {
        $this->connection->runCommand('create', 'test_collection');

        $this->assertTrue($this->schema->hasDatabase('TEST'));
        $this->assertFalse($this->schema->hasDatabase('NOT_FOUND_DB'));
    }

    /**
     *
     */
    public function test_hasTable()
    {
        $this->connection->runCommand('create', 'test_collection');

        $this->assertTrue($this->schema->hasTable('test_collection'));
        $this->assertFalse($this->schema->hasTable('NOT_FOUND_TABLE'));
    }

    /**
     *
     */
    public function test_drop()
    {
        $this->connection->runCommand('create', 'test_collection');
        $this->assertTrue($this->schema->hasTable('test_collection'));

        $this->schema->drop('test_collection');
        $this->assertFalse($this->schema->hasTable('test_collection'));
    }

    /**
     *
     */
    public function test_truncate()
    {
        $this->connection->from('test_collection')->insert(['foo' => 'bar']);
        $this->connection->from('test_collection')->insert(['foo' => 'bar']);
        $this->assertCount(2, $this->connection->from('test_collection')->all());

        $this->schema->truncate('test_collection');
        $this->assertEmpty($this->connection->from('test_collection')->all());
    }

    /**
     *
     */
    public function test_rename()
    {
        $this->connection->from('test_collection')->insert(['foo' => 'bar']);
        $this->connection->from('test_collection')->insert(['foo' => 'baz']);

        $this->schema->rename('test_collection', 'new_name');
        $this->assertEquals(['bar', 'baz'], $this->connection->from('new_name')->inRows('foo'));

        $this->assertFalse($this->schema->hasTable('test_collection'));
    }

    /**
     *
     */
    public function test_loadSchema()
    {
        $this->connection->runCommand('create', 'test_collection');
        $this->connection->runCommand('create', 'other');

        $schema = $this->schema->loadSchema();

        $this->assertEquals(['test.test_collection', 'test.other'], $schema->getTableNames());
    }
}
