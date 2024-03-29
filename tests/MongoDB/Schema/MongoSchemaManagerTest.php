<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\MongoDB\Test\EntityWithCustomCollation;
use Bdf\Prime\Schema\Adapter\Metadata\MetadataTable;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoAssertion;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Query\Command\CreateIndexes;
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

        $this->schema = new MongoSchemaManager($this->connection);
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
    public function test_load()
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

        $collection = $this->schema->load('test_collection');

        $this->assertInstanceOf(CollectionDefinition::class, $collection);
        $this->assertEquals('test_collection', $collection->name());

        $this->assertEquals('search_name', $collection->indexes()->get('search_name')->name());
        $this->assertEquals(['first_name', 'last_name'], $collection->indexes()->get('search_name')->fields());
        $this->assertTrue($collection->indexes()->get('search_name')->unique());

        $this->assertEquals('age_order', $collection->indexes()->get('age_order')->name());
        $this->assertEquals(['age'], $collection->indexes()->get('age_order')->fields());
        $this->assertEquals(Index::TYPE_SIMPLE, $collection->indexes()->get('age_order')->type());

        $this->assertEquals(MongoIndex::PRIMARY, $collection->indexes()->get(MongoIndex::PRIMARY)->name());
        $this->assertEquals(['_id'], $collection->indexes()->get(MongoIndex::PRIMARY)->fields());
        $this->assertTrue($collection->indexes()->get(MongoIndex::PRIMARY)->primary());

        $this->assertEquals(['_id'], $collection->indexes()->primary()->fields());
    }

    /**
     *
     */
    public function test_add_and_load()
    {
        $collection = (new CollectionDefinitionBuilder('test_collection'))
            ->capped()
            ->size(4096)
            ->collation(['locale' => 'en', 'strength' => 2])
            ->indexes(function (IndexBuilder $builder) {
                $builder->add('foo_idx')->on('foo');
            })
            ->build()
        ;

        $this->schema->add($collection);

        $loaded = $this->schema->load('test_collection');

        $this->assertEquals('test_collection', $loaded->name());
        $this->assertCount(2, $loaded->indexes()->all());
        $this->assertEquals(['foo'], $loaded->indexes()->get('foo_idx')->fields());
        $this->assertEquals([
            'capped' => true,
            'size' => 4096,
            'collation' => [
                'locale' => 'en',
                'strength' => 2,
                'caseLevel' => false,
                'caseFirst' => 'off',
                'numericOrdering' => false,
                'alternate' => 'non-ignorable',
                'maxVariable' => 'punct',
                'normalization' => false,
                'backwards' => false,
                'version' => '57.1',
            ]
        ], $loaded->options());
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
            new Index(['name_' => []], Index::TYPE_SIMPLE, 'name')
        ]));

        $diff = $this->schema->diff($table2, $table);

        $this->assertInstanceOf(IndexSetDiff::class, $diff);
        $this->assertEquals('table_', $diff->collection());
        $this->assertCount(1, $diff->commands());

        $this->assertInstanceOf(CreateIndexes::class, $diff->commands()[0]);
        $this->assertEquals([
            'createIndexes' => 'table_',
            'indexes' => [
                [
                    'key' => [
                        'name_' => 1
                    ],
                    'name' => 'name'
                ]
            ]
        ], $diff->commands()[0]->document());
    }

    /**
     *
     */
    public function test_diff_with_collection()
    {
        $collection1 = new CollectionDefinition('table_', new IndexSet([]), []);
        $collection2 = new CollectionDefinition('table_', new IndexSet([
            new Index(['name_' => []], Index::TYPE_SIMPLE, 'name')
        ]), []);

        $diff = $this->schema->diff($collection2, $collection1);

        $this->assertInstanceOf(IndexSetDiff::class, $diff);
        $this->assertEquals('table_', $diff->collection());
        $this->assertCount(1, $diff->commands());

        $this->assertInstanceOf(CreateIndexes::class, $diff->commands()[0]);
        $this->assertEquals([
            'createIndexes' => 'table_',
            'indexes' => [
                [
                    'key' => [
                        'name_' => 1
                    ],
                    'name' => 'name'
                ]
            ]
        ], $diff->commands()[0]->document());
    }

    /**
     *
     */
    public function test_create_and_load_table_with_collation()
    {
        EntityWithCustomCollation::repository()->schema()->migrate();
        $table = $this->schema->loadTable('with_collation');

        $this->assertEquals([
            'collation' => [
                'locale' => 'en',
                'strength' => 2,
                'caseLevel' => false,
                'caseFirst' => 'off',
                'numericOrdering' => false,
                'alternate' => 'non-ignorable',
                'maxVariable' => 'punct',
                'normalization' => false,
                'backwards' => false,
                'version' => '57.1',
            ],
        ], $table->options());
    }
}
