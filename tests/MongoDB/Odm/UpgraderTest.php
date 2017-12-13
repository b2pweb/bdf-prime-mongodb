<?php

namespace Bdf\Prime\MongoDB\Odm;

require_once __DIR__.'/../_files/mongo_entities.php';

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoAssertion;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Command\CommandInterface;
use Bdf\Prime\MongoDB\Test\Person;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Resolver;
use MongoDB\Driver\Command;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Odm_Upgrader
 */
class UpgraderTest extends TestCase
{
    use PrimeTestCase;
    use MongoAssertion;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * @var MongoConnection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->primeStart();

        Prime::service()->config()->getDbConfig()->merge([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => '127.0.0.1',
                'dbname' => 'TEST',
            ],
        ]);

        $this->resolver = Person::repository()->schema();
        $this->connection = Prime::connection('mongo');
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

        $this->assertEquals('search_name', $indexes[1]->name);
        $this->assertEquals(1, $indexes[1]->key->first_name);
        $this->assertEquals(1, $indexes[1]->key->last_name);

        $this->assertEquals('age_sort', $indexes[2]->name);
        $this->assertEquals(1, $indexes[2]->key->age);
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
                        'first_name' => 1,
                        'last_name'  => 1
                    ],
                    'name'   => 'search_name',
                    'unique' => 1
                ],
                [
                    'key' => [
                        'age' => 1
                    ],
                    'name' => 'age_sort'
                ]
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
                        'first_name' => 1,
                        'last_name'  => 1
                    ],
                    'name'   => 'search_name',
                    'unique' => 1
                ]
            ]
        ], $diffs[1]->document( ));
    }
}
