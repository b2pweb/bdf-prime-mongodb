<?php

namespace MongoDB\Document;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class MongoDocumentTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoCollection<Person>
     */
    private $collection;

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

        Mongo::configure($locator = new MongoCollectionLocator(Prime::service()->connections()));
        $this->collection = $locator->collection(Person::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    /**
     * @return void
     */
    public function test_get_set_id()
    {
        $person = new Person();

        $r = new ReflectionProperty(MongoDocument::class, '_id');
        $r->setAccessible(true);

        $this->assertNull($person->id());
        $this->assertNull($r->getValue($person));

        $person->setId($id = new ObjectId());
        $this->assertSame($id, $person->id());
        $this->assertSame($id, $r->getValue($person));

        $person->setId(null);
        $this->assertNull($person->id());
        $this->assertNull($r->getValue($person));
    }

    /**
     * @return void
     */
    public function test_get_set_collection()
    {
        $person = new Person();

        $this->assertNull($person->id());
        $this->assertSame($this->collection, $person->collection());
    }

    public function test_save_should_generate_id_and_insert()
    {
        $person = new Person('John', 'Doe');
        $person->save();

        $this->assertNotNull($person->id());
        $this->assertEquals($person, $this->collection->get($person->id()));
    }

    public function test_save_should_insert_if_not_exists()
    {
        $person = new Person('John', 'Doe');
        $person->setId($id = new ObjectId());

        $person->save();

        $this->assertEquals($person, $this->collection->get($id));
    }

    public function test_save_should_replace_if_already_exists()
    {
        $person = new Person('John', 'Doe');
        $this->collection->add($person);

        $person->setFirstName('Jean');
        $person->save();

        $this->assertEquals($person, $this->collection->get($person->id()));
    }

    public function test_update_all_fields()
    {
        $person = new Person('John', 'Doe');
        $this->collection->add($person);

        $person->setFirstName('Jean');
        $person->setLastName('Dupont');
        $person->update();

        $this->assertEquals($person, $this->collection->get($person->id()));
    }

    public function test_update_with_fields()
    {
        $person = new Person('John', 'Doe');
        $this->collection->add($person);

        $person->setFirstName('Jean');
        $person->setLastName('Dupont');
        $person->update(['firstName']);

        $this->assertEquals('Jean', $this->collection->get($person->id())->firstName());
        $this->assertEquals('Doe', $this->collection->get($person->id())->lastName());
    }

    public function test_insert_should_generate_id_and_insert()
    {
        $person = new Person('John', 'Doe');
        $person->insert();

        $this->assertNotNull($person->id());
        $this->assertEquals($person, $this->collection->get($person->id()));
    }

    public function test_insert_should_insert_if_not_exists()
    {
        $person = new Person('John', 'Doe');
        $person->setId($id = new ObjectId());

        $person->insert();

        $this->assertEquals($person, $this->collection->get($id));
    }

    public function test_delete()
    {
        $person = new Person('John', 'Doe');
        $this->collection->add($person);

        $person->delete();

        $this->assertNull($this->collection->get($person->id()));
    }

    public function test_facade_methods()
    {
        $person = new Person('John', 'Doe');

        $this->assertSame($this->collection, Person::collection());
        $person->insert();

        $this->assertEquals($person, Person::get($person->id()));
        $this->assertEquals($person, Person::where('firstName', 'John')->first());
        $this->assertEquals($person, Person::query()->where('firstName', 'John')->first());
        $this->assertEquals($person, Person::findOneRaw(['firstName' => 'John']));
        $this->assertEquals([$person], iterator_to_array(Person::findAllRaw(['firstName' => 'John'])));
        $this->assertEquals(1, Person::count());
        $this->assertEquals(1, Person::count(['firstName' => 'John']));
        $this->assertEquals(0, Person::count(['firstName' => 'NotFound']));
        $this->assertTrue(Person::exists($person));
        $this->assertFalse(Person::exists(new Person()));
        $this->assertEquals($person, Person::refresh($person));
        $this->assertEquals($person, Person::refresh((new Person())->setId($person->id())));
        $this->assertNull(Person::refresh(new Person()));
    }
}

class Person extends MongoDocument
{
    private ?string $firstName;
    private ?string $lastName;
    private ?\DateTime $birthDate;

    public function __construct(?string $firstName = null, ?string $lastName = null, ?\DateTime $birthDate = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->birthDate = $birthDate;
    }

    /**
     * @return string
     */
    public function firstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return Person
     */
    public function setFirstName(string $firstName): Person
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function lastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return Person
     */
    public function setLastName(string $lastName): Person
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function birthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    /**
     * @param \DateTime|null $birthDate
     * @return Person
     */
    public function setBirthDate(?\DateTime $birthDate): Person
    {
        $this->birthDate = $birthDate;
        return $this;
    }
}

class PersonMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'persons';
    }
}
