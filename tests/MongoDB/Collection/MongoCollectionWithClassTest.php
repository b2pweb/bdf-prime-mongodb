<?php

namespace MongoDB\Collection;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

class MongoCollectionWithClassTest extends TestCase
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
    public function test_add_and_get()
    {
        $doc = new Person('John', 'Doe');

        $this->collection->add($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->id());
        $this->assertSame($this->collection, $doc->collection());

        $this->assertEquals($doc, $this->collection->get($doc->id()));
    }

    /**
     * @return void
     */
    public function test_add_with_id()
    {
        $id = new ObjectId();
        $doc = new Person('John', 'Doe');
        $doc->setId($id);

        $this->collection->add($doc);
        $this->assertEquals($id, $doc->id());
        $this->assertSame($this->collection, $doc->collection());

        $this->assertEquals($doc, $this->collection->get($id));
    }

    /**
     * @return void
     */
    public function test_replace()
    {
        $doc = new Person('John', 'Doe');

        $this->collection->replace($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->id());
        $this->assertEquals($doc, $this->collection->get($doc->id()));
        $this->assertSame($this->collection, $doc->collection());

        $doc->setLastName('baz');
        $this->collection->replace($doc);
        $this->assertEquals($doc, $this->collection->get($doc->id()));
    }

    /**
     * @return void
     */
    public function test_replace_with_id()
    {
        $id = new ObjectId();
        $doc = new Person('John', 'Doe');
        $doc->setId($id);

        $this->collection->replace($doc);
        $this->assertSame($id, $doc->id());
        $this->assertEquals($doc, $this->collection->get($id));
        $this->assertSame($this->collection, $doc->collection());
    }

    /**
     * @return void
     */
    public function test_delete_without_id()
    {
        $doc = new Person('John', 'Doe');

        $this->collection->delete($doc);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $doc = new Person('John', 'Doe');
        $doc->setId(new ObjectId());

        $this->collection->add($doc);
        $this->collection->delete($doc);

        $this->assertNull($this->collection->get($doc->id()));
        $this->collection->delete($doc);
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $doc = new Person('John', 'Doe');
        $this->collection->add($doc);

        $doc->setFirstName('Michel');
        $doc->setLastName('Smith');

        $this->collection->update($doc, ['lastName']);

        $this->assertSame('John', $this->collection->get($doc->id())->firstName());
        $this->assertSame('Smith', $this->collection->get($doc->id())->lastName());
    }

    /**
     * @return void
     */
    public function test_update_all_fields()
    {
        $doc = new Person('John', 'Doe');
        $this->collection->add($doc);

        $doc->setFirstName('Michel');
        $doc->setLastName('Smith');

        $this->collection->update($doc);

        $this->assertSame('Michel', $this->collection->get($doc->id())->firstName());
        $this->assertSame('Smith', $this->collection->get($doc->id())->lastName());
    }

    /**
     * @return void
     */
    public function test_findOneRaw()
    {
        $doc1 = new Person('John', 'Doe');
        $doc2 = new Person('Albert', 'Dupont');
        $doc3 = new Person('Jean', 'Le Bon', new \DateTime('1319-04-26'));

        $this->collection->add($doc1);
        $this->collection->add($doc2);
        $this->collection->add($doc3);

        $this->assertEquals($doc1, $this->collection->findOneRaw(['firstName' => 'John']));
        $this->assertEquals($doc2, $this->collection->findOneRaw(['firstName' => ['$regex' => '.*bert']]));
        $this->assertEquals($doc3, $this->collection->findOneRaw(['birthDate' => ['$type' => 'date']]));
        $this->assertNull($this->collection->findOneRaw(['not' => 'found']));

        $this->assertSame($this->collection, $this->collection->findOneRaw(['firstName' => 'John'])->collection());
    }

    /**
     * @return void
     */
    public function test_findAllRaw()
    {
        $doc1 = new Person('John', 'Doe');
        $doc2 = new Person('Albert', 'Dupont');
        $doc3 = new Person('Jean', 'Le Bon', new \DateTime('1319-04-26'));

        $this->collection->add($doc1);
        $this->collection->add($doc2);
        $this->collection->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], $this->collection->findAllRaw(['firstName' => 'John']));
        $this->assertEqualsCanonicalizing([$doc1, $doc2], $this->collection->findAllRaw(['lastName' => ['$regex' => '^d.*', '$options' => 'i']]));
        $this->assertEquals([$doc3], $this->collection->findAllRaw(['birthDate' => ['$lt' => new UTCDateTime(1000)]]));
        $this->assertEqualsCanonicalizing([], $this->collection->findAllRaw(['birthDate' => 'bob']));

        $this->assertSame($this->collection, $this->collection->findAllRaw(['firstName' => 'John'])[0]->collection());
    }

    /**
     * @return void
     */
    public function test_query()
    {
        $doc1 = new Person('John', 'Doe');
        $doc2 = new Person('Albert', 'Dupont');
        $doc3 = new Person('Jean', 'Le Bon', new \DateTime('1319-04-26'));

        $this->collection->add($doc1);
        $this->collection->add($doc2);
        $this->collection->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], $this->collection->query()->where('firstName', 'John')->all());
        $this->assertEqualsCanonicalizing([$doc1, $doc2], $this->collection->query()->where('lastName', (new Like('d'))->startsWith())->all());
        $this->assertEquals([$doc3], $this->collection->query()->whereRaw(['birthDate' => ['$type' => 'date']])->all());
        $this->assertEquals([], $this->collection->query()->whereRaw(['firstName' => ['$exists' => false]])->all());

        $this->assertSame($this->collection, $this->collection->query()->where('firstName', 'John')->first()->collection());
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
