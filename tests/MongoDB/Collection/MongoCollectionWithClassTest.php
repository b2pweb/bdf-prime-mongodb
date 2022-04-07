<?php

namespace MongoDB\Collection;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
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
    private $customClassDocument;

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
        $this->customClassDocument = $locator->collection(Person::class);
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

        $this->customClassDocument->add($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->id());
        $this->assertSame($this->customClassDocument, $doc->collection());

        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));
    }

    /**
     * @return void
     */
    public function test_add_with_id()
    {
        $id = new ObjectId();
        $doc = new Person('John', 'Doe');
        $doc->setId($id);

        $this->customClassDocument->add($doc);
        $this->assertEquals($id, $doc->id());
        $this->assertSame($this->customClassDocument, $doc->collection());

        $this->assertEquals($doc, $this->customClassDocument->get($id));
    }

    /**
     * @return void
     */
    public function test_replace()
    {
        $doc = new Person('John', 'Doe');

        $this->customClassDocument->replace($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->id());
        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));
        $this->assertSame($this->customClassDocument, $doc->collection());

        $doc->setLastName('baz');
        $this->customClassDocument->replace($doc);
        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));
    }

    /**
     * @return void
     */
    public function test_replace_with_id()
    {
        $id = new ObjectId();
        $doc = new Person('John', 'Doe');
        $doc->setId($id);

        $this->customClassDocument->replace($doc);
        $this->assertSame($id, $doc->id());
        $this->assertEquals($doc, $this->customClassDocument->get($id));
        $this->assertSame($this->customClassDocument, $doc->collection());
    }

    /**
     * @return void
     */
    public function test_delete_without_id()
    {
        $doc = new Person('John', 'Doe');

        $this->customClassDocument->delete($doc);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $doc = new Person('John', 'Doe');
        $doc->setId(new ObjectId());

        $this->customClassDocument->add($doc);
        $this->customClassDocument->delete($doc);

        $this->assertNull($this->customClassDocument->get($doc->id()));
        $this->customClassDocument->delete($doc);
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $doc = new Person('John', 'Doe');
        $this->customClassDocument->add($doc);

        $doc->setFirstName('Michel');
        $doc->setLastName('Smith');

        $this->customClassDocument->update($doc, ['lastName']);

        $this->assertSame('John', $this->customClassDocument->get($doc->id())->firstName());
        $this->assertSame('Smith', $this->customClassDocument->get($doc->id())->lastName());
    }

    /**
     * @return void
     */
    public function test_update_all_fields()
    {
        $doc = new Person('John', 'Doe');
        $this->customClassDocument->add($doc);

        $doc->setFirstName('Michel');
        $doc->setLastName('Smith');

        $this->customClassDocument->update($doc);

        $this->assertSame('Michel', $this->customClassDocument->get($doc->id())->firstName());
        $this->assertSame('Smith', $this->customClassDocument->get($doc->id())->lastName());
    }

    /**
     * @return void
     */
    public function test_findOneRaw()
    {
        $doc1 = new Person('John', 'Doe');
        $doc2 = new Person('Albert', 'Dupont');
        $doc3 = new Person('Jean', 'Le Bon', new \DateTime('1319-04-26'));

        $this->customClassDocument->add($doc1);
        $this->customClassDocument->add($doc2);
        $this->customClassDocument->add($doc3);

        $this->assertEquals($doc1, $this->customClassDocument->findOneRaw(['firstName' => 'John']));
        $this->assertEquals($doc2, $this->customClassDocument->findOneRaw(['firstName' => ['$regex' => '.*bert']]));
        $this->assertEquals($doc3, $this->customClassDocument->findOneRaw(['birthDate' => ['$type' => 'date']]));
        $this->assertNull($this->customClassDocument->findOneRaw(['not' => 'found']));

        $this->assertSame($this->customClassDocument, $this->customClassDocument->findOneRaw(['firstName' => 'John'])->collection());
    }

    /**
     * @return void
     */
    public function test_findAllRaw()
    {
        $doc1 = new Person('John', 'Doe');
        $doc2 = new Person('Albert', 'Dupont');
        $doc3 = new Person('Jean', 'Le Bon', new \DateTime('1319-04-26'));

        $this->customClassDocument->add($doc1);
        $this->customClassDocument->add($doc2);
        $this->customClassDocument->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], iterator_to_array($this->customClassDocument->findAllRaw(['firstName' => 'John'])));
        $this->assertEqualsCanonicalizing([$doc1, $doc2], iterator_to_array($this->customClassDocument->findAllRaw(['lastName' => ['$regex' => '^d.*', '$options' => 'i']])));
        $this->assertEquals([$doc3], iterator_to_array($this->customClassDocument->findAllRaw(['birthDate' => ['$lt' => new UTCDateTime(1000)]])));
        $this->assertEqualsCanonicalizing([], iterator_to_array($this->customClassDocument->findAllRaw(['birthDate' => 'bob'])));

        $this->assertSame($this->customClassDocument, iterator_to_array($this->customClassDocument->findAllRaw(['firstName' => 'John']))[0]->collection());
    }

    /**
     * @return void
     */
    public function test_query()
    {
        $doc1 = new Person('John', 'Doe');
        $doc2 = new Person('Albert', 'Dupont');
        $doc3 = new Person('Jean', 'Le Bon', new \DateTime('1319-04-26'));

        $this->customClassDocument->add($doc1);
        $this->customClassDocument->add($doc2);
        $this->customClassDocument->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], iterator_to_array($this->customClassDocument->query()->where('firstName', 'John')->all()));
        $this->assertEqualsCanonicalizing([$doc1, $doc2], iterator_to_array($this->customClassDocument->query()->where('lastName', (new Like('d'))->startsWith())->all()));
        $this->assertEquals([$doc3], iterator_to_array($this->customClassDocument->query()->whereRaw(['birthDate' => ['$type' => 'date']])->all()));
        $this->assertEquals([], iterator_to_array($this->customClassDocument->query()->whereRaw(['firstName' => ['$exists' => false]])->all()));

        $this->assertSame($this->customClassDocument, $this->customClassDocument->query()->where('firstName', 'John')->first()->collection());
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
