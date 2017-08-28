<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoAssertion;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;
use Bdf\Prime\Schema\TableInterface;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Schema
 * @group Bdf_Prime_MongoDB_Schema_MongoSchemaManager
 */
class MongoSchemaManagerTest extends TestCase
{
    use MongoAssertion;

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
        $manager->registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

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

        $this->assertInstanceOf(TableInterface::class, $table);
        $this->assertEquals('test_collection', $table->name());

        $this->assertEquals('search_name', $table->indexes()->get('search_name')->name());
        $this->assertEquals(['first_name', 'last_name'], $table->indexes()->get('search_name')->fields());
        $this->assertTrue($table->indexes()->get('search_name')->unique());

        $this->assertEquals('age_order', $table->indexes()->get('age_order')->name());
        $this->assertEquals(['age'], $table->indexes()->get('age_order')->fields());
        $this->assertEquals(Index::TYPE_SIMPLE, $table->indexes()->get('age_order')->type());

        $this->assertEquals(MongoIndex::PRIMARY, $table->indexes()->get(MongoIndex::PRIMARY)->name());
        $this->assertEquals(['_id'], $table->indexes()->get(MongoIndex::PRIMARY)->fields());
        $this->assertTrue($table->indexes()->get(MongoIndex::PRIMARY)->primary());

        $this->assertEquals(['_id'], $table->indexes()->primary()->fields());
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
     * @todo à voir quand on gèrera les schemas
     */
    public function test_loadSchema()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot load Doctrine Schema from MongoDB');

        $this->connection->runCommand('create', 'test_collection');
        $this->connection->runCommand('create', 'other');

        $schema = $this->schema->loadSchema();

        //$this->assertEquals(['test.test_collection', 'test.other'], $schema->getTableNames());
    }

    /**
     *
     */
    public function test_push_simple()
    {
        $schema = $this->schema->simulate();

        $this->assertSame($schema, $schema->push('foo'));
        $schema->push('bar');

        $this->assertEquals(['foo', 'bar'], $schema->pending());
    }

    /**
     *
     */
    public function test_push_array()
    {
        $schema = $this->schema->simulate();

        $this->assertSame($schema, $schema->push(['foo', 'bar']));

        $this->assertEquals(['foo', 'bar'], $schema->pending());
    }

    /**
     *
     */
    public function test_push_CommandSet()
    {
        $schema = $this->schema->simulate();

        $set = $this->createMock(CommandSetInterface::class);

        $set->expects($this->once())
            ->method('commands')
            ->willReturn(['foo', 'bar'])
        ;

        $this->assertSame($schema, $schema->push($set));

        $this->assertEquals(['foo', 'bar'], $schema->pending());
    }

    /**
     *
     */
    public function test_clear()
    {
        $schema = $this->schema->simulate();

        $schema
            ->push('foo')
            ->push('bar')
        ;

        $this->assertSame($schema, $schema->clear());
        $this->assertEmpty($schema->pending());
    }

    /**
     *
     */
    public function test_simulate()
    {
        $schema = $this->schema->simulate();

        $schema
            ->dropDatabase('db')
            ->drop('my_collection')
            ->truncate('other_collection')
            ->rename('other_collection', 'new_name')
        ;

        $this->assertCount(4, $schema->pending());
    }

    /**
     *
     */
    public function test_schema()
    {
        $table = new Table('table_', [], new IndexSet([]));

        $this->assertEquals(new SchemaCreation([$table]), $this->schema->schema($table));
    }

    /**
     *
     */
    public function test_diff()
    {
        $table = new Table('table_', [], new IndexSet([]));
        $table2 = new Table('table_', [], new IndexSet([
            new Index(['name_'], Index::TYPE_SIMPLE, 'name')
        ]));

        $diff = $this->schema->diff($table2, $table);

        $this->assertInstanceOf(IndexSetDiff::class, $diff);
        $this->assertEquals('table_', $diff->collection());
        $this->assertCount(1, $diff->commands());

        $this->assertCommand([
            'createIndexes' => 'table_',
            'indexes' => [
                [
                    'key' => [
                        'name_' => 1
                    ],
                    'name' => 'name'
                ]
            ]
        ], $diff->commands()[0]);
    }
}
