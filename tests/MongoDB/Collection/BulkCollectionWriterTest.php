<?php

namespace MongoDB\Collection;

use Bdf\Prime\MongoDB\Collection\BulkCollectionWriter;
use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\TestDocument\PersonDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class BulkCollectionWriterTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoCollection<PersonDocument>
     */
    private $collection;

    /**
     * @var BulkCollectionWriter<PersonDocument>
     */
    private $writer;

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
        $this->collection = $locator->collection(PersonDocument::class);
        $this->writer = new BulkCollectionWriter($this->collection);
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
    public function test_insert_one()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');

        $this->assertEquals(1, $this->writer->insert($doc));
        $this->assertEquals(1, $this->writer->pending());
        $this->assertInstanceOf(ObjectId::class, $doc->id());
        $this->assertNull($this->collection->refresh($doc));

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEquals($doc, $this->collection->refresh($doc));
    }

    /**
     * @return void
     */
    public function test_insert_with_id()
    {
        $id = new ObjectId();
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');
        $doc->setId($id);

        $this->assertEquals(1, $this->writer->insert($doc));
        $this->assertEquals(1, $this->writer->pending());
        $this->assertEquals($id, $doc->id());
        $this->assertNull($this->collection->refresh($doc));

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEquals($doc, $this->collection->refresh($doc));
    }

    /**
     * @return void
     */
    public function test_insert_replace()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');

        $this->assertEquals(1, $this->writer->insert($doc, ['replace' => true]));
        $this->assertEquals(1, $this->writer->pending());
        $this->assertInstanceOf(ObjectId::class, $doc->id());
        $this->assertNull($this->collection->refresh($doc));

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEquals($doc, $this->collection->refresh($doc));

        $doc->setLastName('baz');
        $this->assertEquals(1, $this->writer->insert($doc, ['replace' => true]));
        $this->assertEquals(1, $this->writer->pending());
        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEquals($doc, $this->collection->refresh($doc));
    }

    /**
     * @return void
     */
    public function test_insert_replace_with_id()
    {
        $id = new ObjectId();
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');
        $doc->setId($id);

        $this->assertEquals(1, $this->writer->insert($doc, ['replace' => true]));
        $this->assertEquals(1, $this->writer->pending());
        $this->assertSame($id, $doc->id());
        $this->assertNull($this->collection->refresh($doc));

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEquals($doc, $this->collection->refresh($doc));
    }

    /**
     * @return void
     */
    public function test_delete_without_id()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');

        $this->assertEquals(0, $this->writer->delete($doc));
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEquals(0, $this->writer->flush());
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');
        $doc->setId(new ObjectId());

        $this->collection->add($doc);

        $this->assertEquals(1, $this->writer->delete($doc));
        $this->assertEquals(1, $this->writer->pending());
        $this->assertNotNull($this->collection->refresh($doc));

        $this->assertEquals(1, $this->writer->flush());
        $this->assertNull($this->collection->refresh($doc));

        $this->assertEquals(1, $this->writer->delete($doc));
        $this->assertEquals(0, $this->writer->flush());
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');
        $this->collection->add($doc);

        $doc->setFirstName('Michel');
        $doc->setLastName('Smith');

        $this->assertEquals(1, $this->writer->update($doc, ['attributes' => ['lastName']]));
        $this->assertEquals(1, $this->writer->pending());

        $this->assertSame('John', $this->collection->refresh($doc)->firstName());
        $this->assertSame('Doe', $this->collection->refresh($doc)->lastName());

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());

        $this->assertSame('John', $this->collection->refresh($doc)->firstName());
        $this->assertSame('Smith', $this->collection->refresh($doc)->lastName());
    }

    /**
     * @return void
     */
    public function test_update_all_fields()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');
        $this->collection->add($doc);

        $doc->setFirstName('Michel');
        $doc->setLastName('Smith');

        $this->assertEquals(1, $this->writer->update($doc));
        $this->assertEquals(1, $this->writer->pending());

        $this->assertSame('John', $this->collection->refresh($doc)->firstName());
        $this->assertSame('Doe', $this->collection->refresh($doc)->lastName());

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());

        $this->assertSame('Michel', $this->collection->refresh($doc)->firstName());
        $this->assertSame('Smith', $this->collection->refresh($doc)->lastName());
    }

    /**
     * @return void
     */
    public function test_update_not_exists()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');
        $doc->setId(new ObjectId());

        $this->assertEquals(1, $this->writer->update($doc));
        $this->assertEquals(1, $this->writer->pending());
        $this->assertEquals(0, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
    }

    /**
     * @return void
     */
    public function test_update_without_id_should_failed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The document id is missing');

        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');

        $this->writer->update($doc);
    }

    public function test_perform_multiple_operations()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');

        $this->writer->insert($doc);

        $doc->setFirstName('Jean');
        $this->writer->update($doc);

        $doc2 = clone $doc;
        $doc2->setId(null);

        $this->writer->insert($doc2);
        $this->writer->delete($doc2);

        $this->assertEquals(4, $this->writer->pending());
        $this->assertEquals(4, $this->writer->flush());

        $this->assertEquals($doc, $this->collection->refresh($doc));
        $this->assertNull($this->collection->refresh($doc2));

        $this->assertEquals(0, $this->writer->pending());
    }

    public function test_destructor_should_flush()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');

        $this->writer->insert($doc);
        $this->writer = null;

        $this->assertEquals($doc, $this->collection->refresh($doc));
    }

    public function test_clear()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('John');
        $doc->setLastName('Doe');

        $this->writer->insert($doc);
        $this->assertEquals(1, $this->writer->pending());

        $this->writer->clear();
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEquals(0, $this->writer->flush());
        $this->assertNull(PersonDocument::refresh($doc));
    }
}
