<?php

namespace MongoDB\Collection;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClass;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class MongoCollectionWithClassNotMongoDocumentTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoCollection<DocumentWithoutBaseClass>
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
        $this->customClassDocument = $locator->collection(DocumentWithoutBaseClass::class);
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
        $doc = new DocumentWithoutBaseClass('John');

        $this->customClassDocument->add($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->id());

        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));
    }

    /**
     * @return void
     */
    public function test_add_with_id()
    {
        $id = new ObjectId();
        $doc = new DocumentWithoutBaseClass('John');
        $doc->setId($id);

        $this->customClassDocument->add($doc);
        $this->assertEquals($id, $doc->id());

        $this->assertEquals($doc, $this->customClassDocument->get($id));
    }

    /**
     * @return void
     */
    public function test_replace()
    {
        $doc = new DocumentWithoutBaseClass('John');

        $this->customClassDocument->replace($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->id());
        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));

        $doc->setValue('baz');
        $this->customClassDocument->replace($doc);
        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));
    }

    /**
     * @return void
     */
    public function test_replace_with_id()
    {
        $id = new ObjectId();
        $doc = new DocumentWithoutBaseClass('John');
        $doc->setId($id);

        $this->customClassDocument->replace($doc);
        $this->assertSame($id, $doc->id());
        $this->assertEquals($doc, $this->customClassDocument->get($id));
    }

    /**
     * @return void
     */
    public function test_delete_without_id()
    {
        $doc = new DocumentWithoutBaseClass('John');

        $this->customClassDocument->delete($doc);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $doc = new DocumentWithoutBaseClass('John');
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
        $doc = new DocumentWithoutBaseClass('John');
        $this->customClassDocument->add($doc);

        $doc->setValue('Michel');

        $this->customClassDocument->update($doc);

        $this->assertSame('Michel', $this->customClassDocument->get($doc->id())->value());
    }

    /**
     * @return void
     */
    public function test_findOneRaw()
    {
        $doc1 = new DocumentWithoutBaseClass('John');
        $doc2 = new DocumentWithoutBaseClass('Albert');
        $doc3 = new DocumentWithoutBaseClass('Jean');

        $this->customClassDocument->add($doc1);
        $this->customClassDocument->add($doc2);
        $this->customClassDocument->add($doc3);

        $this->assertEquals($doc1, $this->customClassDocument->findOneRaw(['value' => 'John']));
        $this->assertEquals($doc2, $this->customClassDocument->findOneRaw(['value' => ['$regex' => '.*bert']]));
        $this->assertNull($this->customClassDocument->findOneRaw(['not' => 'found']));
    }

    /**
     * @return void
     */
    public function test_findAllRaw()
    {
        $doc1 = new DocumentWithoutBaseClass('John');
        $doc2 = new DocumentWithoutBaseClass('Albert');
        $doc3 = new DocumentWithoutBaseClass('Jean');

        $this->customClassDocument->add($doc1);
        $this->customClassDocument->add($doc2);
        $this->customClassDocument->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], $this->customClassDocument->findAllRaw(['value' => 'John']));
        $this->assertEqualsCanonicalizing([$doc1, $doc3], $this->customClassDocument->findAllRaw(['value' => ['$regex' => '^j.*', '$options' => 'i']]));
        $this->assertEqualsCanonicalizing([], $this->customClassDocument->findAllRaw(['value' => 'bob']));
    }

    /**
     * @return void
     */
    public function test_query()
    {
        $doc1 = new DocumentWithoutBaseClass('John');
        $doc2 = new DocumentWithoutBaseClass('Albert');
        $doc3 = new DocumentWithoutBaseClass('Jean');

        $this->customClassDocument->add($doc1);
        $this->customClassDocument->add($doc2);
        $this->customClassDocument->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], $this->customClassDocument->query()->where('value', 'John')->all());
        $this->assertEqualsCanonicalizing([$doc1, $doc3], $this->customClassDocument->query()->where('value', (new Like('j'))->startsWith())->all());
        $this->assertEquals([], $this->customClassDocument->query()->whereRaw(['value' => ['$exists' => false]])->all());
    }
}
