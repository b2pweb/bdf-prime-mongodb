<?php

namespace Bdf\Prime\MongoDB\Odm;

require_once __DIR__.'/../_files/mongo_entities.php';

use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use Bdf\Prime\MongoDB\Test\EntityWithCustomCollation;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Test\Address;
use Bdf\Prime\MongoDB\Test\EntityWithComplexArray;
use Bdf\Prime\MongoDB\Test\EntityWithEmbedded;
use Bdf\Prime\MongoDB\Test\Home;
use Bdf\Prime\MongoDB\Test\Person;
use Bdf\Prime\MongoDB\Test\TimestampableEntity;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Value;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;

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
    protected function setUp(): void
    {
        $this->primeStart();

        Prime::service()->connections()->declareConnection('mongo', [
            'driver' => 'mongodb',
            'host'   => $_ENV['MONGO_HOST'],
            'dbname' => 'TEST',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
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

        /*
         * @fixme Value vraiment bon ?
         * Que faire d'un :eq avec un array quand type array ?
         * OrmPreprocessor:229 => check si type->phpType() !== 'array' ?
         */
        $beaufs = Person::find(['tags' => new Value(['beauf', 'corse'])]);

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

        $query = Prime::connection('mongo')->from('embedded_test');
        $data = Prime::connection('mongo')->execute($query)
            ->fetchMode(CursorResultSet::FETCH_RAW_ARRAY)
            ->all();

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
    public function test_timestampable_entity()
    {
        $entity = new TimestampableEntity([
            'value' => 'foo'
        ]);

        $entity->insert();

        $data = Prime::connection('mongo')->from('timestampable')->execute();

        $this->assertInstanceOf(UTCDateTime::class, $data[0]['created_at']);
        $this->assertNull($data[0]['updated_at']);

        $this->assertEqualsWithDelta($entity, TimestampableEntity::refresh($entity), 1);

        $entity = TimestampableEntity::refresh($entity);
        $this->assertInstanceOf(\DateTime::class, $entity->createdAt());
        $this->assertNull($entity->updatedAt());

        $entity->setValue('foo2')->save();

        $data = Prime::connection('mongo')->from('timestampable')->execute();
        $this->assertInstanceOf(UTCDateTime::class, $data[0]['created_at']);
        $this->assertInstanceOf(UTCDateTime::class, $data[0]['updated_at']);

        $entity = TimestampableEntity::refresh($entity);
        $this->assertInstanceOf(\DateTime::class, $entity->createdAt());
        $this->assertInstanceOf(\DateTime::class, $entity->updatedAt());
    }

    /**
     *
     */
    public function test_count()
    {
        $this->addPersons();

        $this->assertEquals(2, Person::count(['firstName :like' => '%e%']));
    }

    /**
     *
     */
    public function test_with_complex_array()
    {
        $entity = new EntityWithComplexArray([
            'addresses' => [
                new Address([
                    'address' => '178 Rue du chanvre',
                    'zipCode' => '39250',
                    'city'    => 'Longcochon',
                    'country' => 'France'
                ]),
                new Address([
                    'address' => 'Longwood House',
                    'zipCode' => 'STHL 1ZZ',
                    'city'    => 'Jamestown',
                    'country' => 'Sainte-Hélène'
                ])
            ]
        ]);

        $entity->save();

        $this->assertEquals($entity, EntityWithComplexArray::refresh($entity));
        $this->assertContainsOnlyInstancesOf(Address::class, EntityWithComplexArray::refresh($entity)->addresses);
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

    /**
     *
     */
    public function test_with_case_insensitive_collation()
    {
        EntityWithCustomCollation::repository()->schema()->migrate();

        $e1 = new EntityWithCustomCollation(['name' => 'Foo']);
        $e2 = new EntityWithCustomCollation(['name' => 'Bar']);

        $e1->insert();
        $e2->insert();

        $this->assertEquals($e1, EntityWithCustomCollation::where('name', 'foo')->first());
        $this->assertEquals($e1, EntityWithCustomCollation::where('name', 'FOO')->first());
        $this->assertEquals($e1, EntityWithCustomCollation::where('name', 'Foo')->first());
        $this->assertEquals($e2, EntityWithCustomCollation::where('name', 'bar')->first());
    }
}
