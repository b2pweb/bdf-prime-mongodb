<?php

namespace MongoDB\Test;

use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Test\MongoTester;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocument;
use Bdf\Prime\MongoDB\TestDocument\PersonDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

class MongoTesterTest extends TestCase
{
    use PrimeTestCase;

    private MongoTester $tester;
    private MongoConnection $connection;

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
        $this->tester = new MongoTester($locator);
        $this->connection = Prime::connection('mongo');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    public function test_declare()
    {
        $this->tester->declare(PersonDocument::class, DiscrimiatorDocument::class);

        $collections = $this->connection->runCommand('listCollections')->toArray();

        $this->assertEqualsCanonicalizing(
            ['person_test', 'with_discriminator'],
            array_map(fn($def) => $def->name, $collections)
        );

        $this->tester->destroy();
        $this->assertEmpty($this->connection->runCommand('listCollections')->toArray());
    }

    public function test_push_should_declare_and_add()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('Jean');
        $doc->setLastName('Robert');

        $this->tester->push($doc);

        $collections = $this->connection->runCommand('listCollections')->toArray();

        $this->assertEqualsCanonicalizing(
            ['person_test'],
            array_map(fn($def) => $def->name, $collections)
        );

        $this->assertEquals($doc, PersonDocument::refresh($doc));

        $this->tester->destroy();
        $this->assertEmpty($this->connection->runCommand('listCollections')->toArray());
    }

    public function test_push_many()
    {
        $doc1 = new PersonDocument();
        $doc1->setFirstName('Jean');
        $doc1->setLastName('Robert');

        $doc2 = new PersonDocument();
        $doc2->setFirstName('Michel');
        $doc2->setLastName('Dupont');

        $doc3 = new PersonDocument();
        $doc3->setFirstName('Louis');
        $doc3->setLastName('Croiver-Baton');

        $this->tester->push([$doc1, $doc2, $doc3]);

        $this->assertEquals($doc1, PersonDocument::refresh($doc1));
        $this->assertEquals($doc2, PersonDocument::refresh($doc2));
        $this->assertEquals($doc3, PersonDocument::refresh($doc3));
    }

    public function test_push_with_key_and_get()
    {
        $doc1 = new PersonDocument();
        $doc1->setFirstName('Jean');
        $doc1->setLastName('Robert');

        $doc2 = new PersonDocument();
        $doc2->setFirstName('Michel');
        $doc2->setLastName('Dupont');

        $doc3 = new PersonDocument();
        $doc3->setFirstName('Louis');
        $doc3->setLastName('Croiver-Baton');

        $this->tester->push(['jean' => $doc1, 'michel' => $doc2, 'louis' => $doc3]);

        $this->assertEquals($doc1, PersonDocument::refresh($doc1));
        $this->assertEquals($doc2, PersonDocument::refresh($doc2));
        $this->assertEquals($doc3, PersonDocument::refresh($doc3));

        $this->assertSame($doc1, $this->tester->get('jean'));
        $this->assertSame($doc2, $this->tester->get('michel'));
        $this->assertSame($doc3, $this->tester->get('louis'));
    }

    public function test_refresh()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('Jean');
        $doc->setLastName('Robert');

        $this->tester->push($doc);

        $this->assertEquals($doc, $this->tester->refresh($doc));
        $this->assertNotSame($doc, $this->tester->refresh($doc));

        $doc->delete();
        $this->assertNull($this->tester->refresh($doc));
    }

    public function test_array_access_with_string()
    {
        $this->assertFalse(isset($this->tester['foo']));


        $doc = new PersonDocument();
        $doc->setFirstName('Jean');
        $doc->setLastName('Robert');

        $this->tester['foo'] = $doc;
        $this->assertTrue(isset($this->tester['foo']));
        $this->assertSame($doc, $this->tester['foo']);
        $this->assertEquals($doc, $this->tester->refresh($doc));

        unset($this->tester['foo']);
        $this->assertFalse(isset($this->tester['foo']));
        $this->assertNull($this->tester->refresh($doc));
    }

    public function test_array_access_with_document()
    {
        $doc = new PersonDocument();
        $doc->setFirstName('Jean');
        $doc->setLastName('Robert');

        $this->assertFalse(isset($this->tester[$doc]));

        $this->tester[] = $doc;
        $this->assertTrue(isset($this->tester[$doc]));
        $this->assertEquals($doc, $this->tester->refresh($doc));
        $this->assertEquals($doc, $this->tester[$doc]);

        unset($this->tester[$doc]);
        $this->assertFalse(isset($this->tester[$doc]));
        $this->assertNull($this->tester->refresh($doc));
    }
}
