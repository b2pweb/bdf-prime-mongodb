<?php

namespace Bdf\Prime\MongoDB\Odm;

require_once __DIR__.'/../_files/mongo_entities.php';

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\MongoDB\Test\Address;
use Bdf\Prime\MongoDB\Test\EntityWithEmbedded;
use Bdf\Prime\MongoDB\Test\Home;
use Bdf\Prime\MongoDB\Test\Person;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use MongoDB\BSON\ObjectID;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Odm
 */
class OdmTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var Person[]
     */
    protected $persons = [];

    /**
     * @var Home[]
     */
    protected $homes = [];

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
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        Prime::connection('mongo')->dropDatabase();
    }

    /**
     *
     */
    public function test_connection()
    {
        $this->assertInstanceOf(MongoConnection::class, Prime::connection('mongo'));
    }

    /**
     *
     */
    public function test_person_all()
    {
        $this->addPersons();

        $persons = Person::all();

        $this->assertEquals(array_values($this->persons), $persons);
    }

    /**
     *
     */
    public function test_person_get()
    {
        $this->addPersons();

        $person = Person::get(2);

        $this->assertEquals($this->persons['louisxvi'], $person);
    }

    /**
     *
     */
    public function test_person_insert_generated_id()
    {
        $person = new Person([
            'firstName' => 'Paul',
            'lastName'  => 'Richon',
            'age'       => 58
        ]);

        $person->insert();

        $this->assertInstanceOf(ObjectID::class, $person->id());

        $this->assertCount(1, Person::all());

        $this->assertEquals($person, Person::get($person->id()));
    }

    /**
     *
     */
    public function test_person_update()
    {
        $this->addPersons();

        $john = $this->persons['john'];

        $john->setAge(32)->update();

        $this->assertEquals(32, Person::get(1)->age());
    }

    /**
     *
     */
    public function test_person_delete()
    {
        $this->addPersons();

        $this->persons['john']->delete();

        $this->assertCount(2, Person::all());
        $this->assertNull(Person::get(1));
    }

    /**
     *
     */
    public function test_home_with_person()
    {
        $this->addHomes();

        /** @var Home $home */
        $home = Home::with('proprietary')->get(1);

        $this->assertEquals($this->persons['john'], $home->proprietary());
    }

    /**
     *
     */
    public function test_search_on_embedded()
    {
        $this->addHomes();

        $homes = Home::with('proprietary')->find(['address.country' => 'France']);

        $this->assertEquals([$this->homes['john_home']], $homes);
    }

    /**
     *
     */
    public function test_searchable_array()
    {
        $this->addPersons();

        $kings = Person::find(['tags' => 'roi']);

        $this->assertCount(1, $kings);
        $this->assertEquals([$this->persons['louisxvi']], $kings);

        $beaufs = Person::find(['tags' => ['beauf', 'corse']]);

        $this->assertEquals([
            $this->persons['john'],
            $this->persons['napoleon']
        ], $beaufs);
    }

    /**
     *
     */
    public function test_insert_non_flatten_embedded()
    {
        $this->addPersons();

        $entity = new EntityWithEmbedded([
            'id' => 1,
            'address' => new Address([
                'address' => '178 Rue du chanvre',
                'zipCode' => '39250',
                'city'    => 'Longcochon',
                'country' => 'France'
            ]),
            'proprietary' => $this->persons['john']
        ]);

        $entity->insert();

        $data = Prime::connection('mongo')->from('embedded_test')->execute()->toArray();

        $this->assertEquals(
            [
                [
                    '_id'     => 1,
                    'address' => [
                        'address' => '178 Rue du chanvre',
                        'zipCode' => '39250',
                        'city'    => 'Longcochon',
                        'country' => 'France'
                    ],
                    'proprietary' => [
                        'id' => $this->persons['john']->id()
                    ]
                ]
            ],
            $data
        );
    }

    /**
     *
     */
    public function test_update_non_flatten_embedded()
    {
        $entity = new EntityWithEmbedded([
            'id' => 1,
            'address' => new Address([
                'address' => '178 Rue du chanvre',
                'zipCode' => '39250',
                'city'    => 'Longcochon',
                'country' => 'France'
            ]),
            'proprietary' => ['id' => 1]
        ]);

        $entity->insert();

        $entity->address()->setCountry('FR');
        $entity->save();

        $this->assertEquals('FR', EntityWithEmbedded::refresh($entity)->address()->country());
    }

    /**
     *
     */
    protected function addPersons()
    {
        $this->persons = [
            'john' => new Person([
                'id' => 1,
                'firstName' => 'John',
                'lastName'  => 'Doe',
                'age' => 36,
                'tags' => ['beauf', 'personne'],
            ]),
            'louisxvi' => new Person([
                'id' => 2,
                'firstName' => 'Louis-Auguste',
                'lastName' => 'De France',
                'age' => 38,
                'tags' => ['décapité', 'roi'],
            ]),
            'napoleon' => new Person([
                'id' => 3,
                'firstName' => 'Napoleone',
                'lastName' => 'di Buonaparte',
                'age' => 51,
                'tags' => ['empereur', 'auto-couronné', 'corse'],
            ])
        ];

        foreach ($this->persons as $person) {
            $person->save();
        }
    }

    /**
     *
     */
    protected function addHomes()
    {
        $this->addPersons();

        $this->homes = [
            'john_home' => new Home([
                'id' => 1,
                'address' => new Address([
                    'address' => '178 Rue du chanvre',
                    'zipCode' => '39250',
                    'city'    => 'Longcochon',
                    'country' => 'France'
                ]),
                'proprietary' => $this->persons['john']
            ]),

            'napoleon_home' => new Home([
                'id' => 2,
                'address' => new Address([
                    'address' => 'Longwood House',
                    'zipCode' => 'STHL 1ZZ',
                    'city'    => 'Jamestown',
                    'country' => 'Sainte-Hélène'
                ])
            ]),
            'proprietary' => $this->persons['napoleon']
        ];

        foreach ($this->homes as $home) {
            $home->save();
        }
    }
}
