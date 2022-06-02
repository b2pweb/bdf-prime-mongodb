<?php

namespace MongoDB\Document\Factory;

use ArrayObject;
use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\Factory\DocumentMapperFactory;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocument;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocumentMapper;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClass;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClassMapper;
use Bdf\Prime\MongoDB\TestDocument\FooDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;
use stdClass;

class DocumentMapperFactoryTest extends TestCase
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

    public function test_createByDocumentClassName()
    {
        $factory = new DocumentMapperFactory();

        $this->assertInstanceOf(DiscrimiatorDocumentMapper::class, $factory->createByDocumentClassName(DiscrimiatorDocument::class));
        $this->assertSame(DiscrimiatorDocument::class, $factory->createByDocumentClassName(DiscrimiatorDocument::class)->document());
        $this->assertInstanceOf(DiscrimiatorDocumentMapper::class, $factory->createByDocumentClassName(FooDocument::class));
        $this->assertSame(FooDocument::class, $factory->createByDocumentClassName(FooDocument::class)->document());
        $this->assertInstanceOf(DocumentWithoutBaseClassMapper::class, $factory->createByDocumentClassName(DocumentWithoutBaseClass::class));
        $this->assertSame(DocumentWithoutBaseClass::class, $factory->createByDocumentClassName(DocumentWithoutBaseClass::class)->document());
        $this->assertNull($factory->createByDocumentClassName(ArrayObject::class));
    }

    public function test_createByMapperClassName()
    {
        $factory = new DocumentMapperFactory();

        $this->assertInstanceOf(DiscrimiatorDocumentMapper::class, $factory->createByMapperClassName(DiscrimiatorDocumentMapper::class));
        $this->assertSame(DiscrimiatorDocument::class, $factory->createByMapperClassName(DiscrimiatorDocumentMapper::class)->document());
        $this->assertInstanceOf(DiscrimiatorDocumentMapper::class, $factory->createByMapperClassName(DiscrimiatorDocumentMapper::class, FooDocument::class));
        $this->assertSame(FooDocument::class, $factory->createByMapperClassName(DiscrimiatorDocumentMapper::class, FooDocument::class)->document());
        $this->assertInstanceOf(WithoutMatchingDocumentClassMapper::class, $factory->createByMapperClassName(WithoutMatchingDocumentClassMapper::class));
        $this->assertSame(stdClass::class, $factory->createByMapperClassName(WithoutMatchingDocumentClassMapper::class)->document());
    }
}
