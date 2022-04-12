<?php

namespace MongoDB\Collection;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocument;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocumentMapper;
use Bdf\Prime\MongoDB\TestDocument\FooDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

class MongoCollectionLocatorTest extends TestCase
{
    use PrimeTestCase;

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

        Mongo::configure(new MongoCollectionLocator(Prime::service()->connections()));
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    public function test_collectionByMapper()
    {
        $locator = new MongoCollectionLocator(Prime::service()->connections());

        $this->assertInstanceOf(MongoCollection::class, $locator->collectionByMapper(DiscrimiatorDocumentMapper::class));
        $this->assertInstanceOf(DiscrimiatorDocumentMapper::class, $locator->collectionByMapper(DiscrimiatorDocumentMapper::class)->mapper());
    }

    public function test_collection()
    {
        $locator = new MongoCollectionLocator(Prime::service()->connections());

        $this->assertSame($locator->collection(FooDocument::class), $locator->collection(FooDocument::class));
        $this->assertInstanceOf(DiscrimiatorDocumentMapper::class, $locator->collection(FooDocument::class)->mapper());

        $this->assertSame($locator->collection(DiscrimiatorDocument::class), $locator->collection(DiscrimiatorDocument::class));
        $this->assertNotSame($locator->collection(DiscrimiatorDocument::class), $locator->collection(FooDocument::class));
        $this->assertInstanceOf(DiscrimiatorDocumentMapper::class, $locator->collection(DiscrimiatorDocument::class)->mapper());
    }
}
