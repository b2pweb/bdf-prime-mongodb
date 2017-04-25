<?php

namespace Bdf\Prime\MongoDB\Orm;

require_once __DIR__ . '/../_files/mongo_entities.php';

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Test\Person;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;


/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Odm
 * @group Bdf_Prime_MongoDB_Odm_TestPack
 */
class TestPackTest extends TestCase
{
    use PrimeTestCase;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configurePrime();

        Prime::service()->config()->getDbConfig()->merge([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => '127.0.0.1',
                'dbname' => 'TEST',
            ],
        ]);

        if ($this->pack()->isInitialized()) {
            $this->pack()->destroy();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        Prime::connection('mongo')->dropDatabase();
        $this->pack()->destroy();
    }

    /**
     *
     */
    public function test_persists()
    {
        $this->pack()->persist([
            'john' => new Person([
                'id' => 1,
                'firstName' => 'John',
                'lastName'  => 'Doe',
                'age' => 36
            ]),
            'louisxvi' => new Person([
                'id' => 2,
                'firstName' => 'Louis-Auguste',
                'lastName' => 'De France',
                'age' => 38
            ]),
            'napoleon' => new Person([
                'id' => 3,
                'firstName' => 'Napoleone',
                'lastName' => 'di Buonaparte',
                'age' => 51
            ])
        ]);

        $this->pack()->initialize();

        $this->assertCount(3, Person::all());
        $this->assertEquals($this->pack()->get('john'), Person::get(1));
        $this->assertEquals($this->pack()->get('louisxvi'), Person::get(2));
        $this->assertEquals($this->pack()->get('napoleon'), Person::get(3));

        $this->pack()->nonPersist([
            'tmp' => new Person([
                'id' => 4,
                'firstName' => 'Temporaire',
                'lastName' => 'A supprimer'
            ])
        ]);

        $this->assertCount(4, Person::all());
        $this->assertEquals($this->pack()->get('tmp'), Person::get(4));

        $this->pack()->clear();

        $this->assertCount(3, Person::all());
        $this->assertNull(Person::get(4));

        $this->pack()->destroy();

        $this->assertEmpty(Person::all());
    }
}
