<?php

namespace Bdf\Prime\MongoDB\Schema;

require_once __DIR__.'/../_files/mongo_documents.php';

use Bdf\Prime\MongoAssertion;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Query\Command\CommandInterface;
use Bdf\Prime\MongoDB\Query\Command\Create;
use Bdf\Prime\MongoDB\Query\Command\CreateIndexes;
use Bdf\Prime\MongoDB\TestDocument\HomeDocument;
use Bdf\Prime\MongoDB\TestDocument\PersonDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use MongoDB\Driver\Command;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CollectionStructureUpgraderTest extends TestCase
{
    use PrimeTestCase;
    use MongoAssertion;

    protected CollectionStructureUpgrader $resolver;
    protected MongoConnection $connection;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->primeStart();

        Prime::service()->connections()->declareConnection('mongo', [
            'driver' => 'mongodb',
            'host'   => $_ENV['MONGO_HOST'],
            'dbname' => 'TEST',
        ]);

        Mongo::configure(new MongoCollectionLocator(Prime::service()->connections()));
        $this->resolver = new CollectionStructureUpgrader(PersonDocument::collection());
        $this->connection = Prime::connection('mongo');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->connection->dropDatabase();
        $this->primeStop();
    }

    /**
     *
     */
    public function test_migrate()
    {
        $this->resolver->migrate(true);

        $collections = $this->connection->runCommand('listCollections')->toArray();

        $this->assertCount(1, $collections);
        $this->assertEquals('person_test', $collections[0]->name);
        $this->assertTrue($this->connection->schema()->hasTable('person_test'));

        $indexes = $this->connection->runCommand('listIndexes', 'person_test')->toArray();
        $this->assertCount(3, $indexes);

        $this->assertEquals('_id_', $indexes[0]->name);
        $this->assertEquals(1, $indexes[0]->key->_id);

        $this->assertEquals('age_sort', $indexes[1]->name);
        $this->assertEquals(1, $indexes[1]->key->age);

        $this->assertEquals('search_name', $indexes[2]->name);
        $this->assertEquals(1, $indexes[2]->key->firstName);
        $this->assertEquals(1, $indexes[2]->key->lastName);
    }

    /**
     *
     */
    public function test_diff_on_creation()
    {
        /** @var Command $diffs[] */
        $diffs = $this->resolver->diff(true);
        $this->assertCount(2, $diffs);

        $this->assertContainsOnly(CommandInterface::class, $diffs);

        $this->assertEquals(['create' => 'person_test'], $diffs[0]->document());

        $this->assertEquals([
            'createIndexes' => 'person_test',
            'indexes'       => [
                [
                    'key' => [
                        'age' => 1
                    ],
                    'name' => 'age_sort'
                ],
                [
                    'key' => [
                        'firstName' => 1,
                        'lastName'  => 1
                    ],
                    'name'   => 'search_name',
                    'unique' => 1
                ],
            ]
        ], $diffs[1]->document());
    }

    /**
     *
     */
    public function test_diff_on_migration()
    {
        $this->connection->runCommand([
            'createIndexes' => 'person_test',
            'indexes'       => [
                [
                    'key' => [
                        'age' => 1
                    ],
                    'name' => 'age_sort'
                ]
            ]
        ]);

        $this->connection->runCommand([
            'createIndexes' => 'person_test',
            'indexes'       => [
                [
                    'key' => [
                        'other' => 1
                    ],
                    'name' => 'to_delete'
                ]
            ]
        ]);

        $diffs = $this->resolver->diff(true);

        $this->assertCount(2, $diffs);

        $this->assertEquals([
            'dropIndexes' => 'person_test',
            'index'       => 'to_delete'
        ], $diffs[0]->document());

        $this->assertEquals([
            'createIndexes' => 'person_test',
            'indexes'       => [
                [
                    'key' => [
                        'firstName' => 1,
                        'lastName'  => 1
                    ],
                    'name'   => 'search_name',
                    'unique' => 1
                ]
            ]
        ], $diffs[1]->document());
    }

    public function test_drop()
    {
        $this->resolver->migrate(true);

        $collections = $this->connection->runCommand('listCollections')->toArray();

        $this->assertCount(1, $collections);
        $this->assertEquals('person_test', $collections[0]->name);
        $this->assertTrue($this->connection->schema()->hasTable('person_test'));

        $this->assertTrue($this->resolver->drop());
        $this->assertEmpty($this->connection->runCommand('listCollections')->toArray());

        $this->resolver->drop(); // true in mongo >= 7, false in mongo < 7
        $this->assertEmpty($this->connection->runCommand('listCollections')->toArray());
    }

    public function test_truncate()
    {
        $this->resolver->migrate(true);
        (new PersonDocument())
            ->setFirstName('John')
            ->setLastName('Doe')
            ->save()
        ;
        (new PersonDocument())
            ->setFirstName('Alan')
            ->setLastName('Smith')
            ->save()
        ;

        $this->assertEquals(2, PersonDocument::count());
        $this->resolver->truncate();
        $this->assertEquals(0, PersonDocument::count());

        $this->assertTrue($this->connection->schema()->hasTable('person_test'));
    }

    /**
     *
     */
    public function test_with_index_options()
    {
        $this->resolver = new CollectionStructureUpgrader(HomeDocument::collection());

        /** @var Command $diffs[] */
        $diffs = $this->resolver->diff(true);
        $this->assertCount(2, $diffs);

        $this->assertEquals([
            'createIndexes' => 'home_test',
            'indexes'       => [
                [
                    'key' => [
                        'address' => 'text',
                        'city' => 'text',
                    ],
                    'name' => 'search',
                    'weights' => [
                        'city' => 2,
                        'address' => 1,
                    ],
                ],
            ]
        ], $diffs[1]->document());

        $this->resolver->migrate();

        // @fixme Diff do not works on text index
        //$this->assertEmpty($this->resolver->diff(true));
    }

    public function test_queries()
    {
        $this->resolver = new CollectionStructureUpgrader(HomeDocument::collection());

        $this->assertEquals([
            'up' => ['mongo' => [
                new Create('home_test'),
                new CreateIndexes('home_test', [
                    [
                        'key' => [
                            'address' => 'text',
                            'city' => 'text',
                        ],
                        'name' => 'search',
                        'weights' => [
                            'city' => 2,
                            'address' => 1,
                        ],
                    ],
                ])
            ]],
            'down' => ['mongo' => []],
        ], $this->resolver->queries(true));
    }
}
