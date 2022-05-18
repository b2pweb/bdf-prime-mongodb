<?php

namespace MongoDB\Schema;

use Bdf\Prime\MongoAssertion;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Schema\CollectionSchemaResolver;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocumentMapper;
use Bdf\Prime\MongoDB\TestDocument\FooDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

class CollectionSchemaResolverTest extends TestCase
{
    use PrimeTestCase;
    use MongoAssertion;

    protected CollectionSchemaResolver $resolver;
    protected MongoConnection $connection;

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
        $this->resolver = new CollectionSchemaResolver($locator);
        $this->connection = Prime::connection('mongo');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->connection->dropDatabase();
        $this->primeStop();
    }

    public function test_resolveByMapperClass()
    {
        $resolver = $this->resolver->resolveByMapperClass(DiscrimiatorDocumentMapper::class);
        $resolver->migrate();

        $this->assertTrue($this->connection->schema()->hasTable('with_discriminator'));
    }

    public function test_resolveByMapperClass_not_found()
    {
        $this->assertNull($this->resolver->resolveByMapperClass(\ArrayObject::class));
    }

    public function test_resolveByDomainClass()
    {
        $resolver = $this->resolver->resolveByDomainClass(FooDocument::class);
        $resolver->migrate();

        $this->assertTrue($this->connection->schema()->hasTable('with_discriminator'));
    }
}
