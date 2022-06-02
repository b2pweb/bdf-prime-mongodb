<?php

namespace MongoDB\Collection;

use BadMethodCallException;
use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\MongoDB\Collection\CollectionQueryExtension;
use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class CollectionQueryExtensionTest extends TestCase
{
    use PrimeTestCase;

    private CollectionQueryExtension $extension;
    private MongoCollection $collection;

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

        $this->collection = new MongoCollection(
            Prime::connection('mongo'),
            new class(\stdClass::class) extends DocumentMapper {
                public function connection(): string
                {
                    return 'mongo';
                }

                public function collection(): string
                {
                    return 'test_simple_object';
                }

                public function scopes(): array
                {
                    return [
                        'search' => function (MongoQuery $query, string $search) {
                            return $query->where('foo', (new Like($search))->contains())->orWhere('bar', (new Like($search))->contains())->all();
                        }
                    ];
                }
            }
        );

        $this->extension = new CollectionQueryExtension($this->collection, $this->collection->mapper());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    public function test_get()
    {
        $this->collection->add($doc1 = (object) ['foo' => 'bar']);
        $this->collection->add($doc2 = (object) ['foo' => 'baz']);

        $this->assertEquals($doc1, $this->query()->get($doc1->_id));
        $this->assertEquals($doc1, $this->query()->where('foo', 'bar')->get($doc1->_id));
        $this->assertNull($this->query()->get(null));
        $this->assertNull($this->query()->get(new ObjectId()));
        $this->assertNull($this->query()->where('foo', 'baz')->get($doc1->_id));
    }

    public function test_getOrFail()
    {
        $this->collection->add($doc1 = (object) ['foo' => 'bar']);
        $this->collection->add($doc2 = (object) ['foo' => 'baz']);

        $this->assertEquals($doc1, $this->query()->getOrFail($doc1->_id));
        $this->assertEquals($doc1, $this->query()->where('foo', 'bar')->getOrFail($doc1->_id));

        try {
            $this->assertNull($this->query()->getOrFail(new ObjectId()));
            $this->fail('Expects EntityNotFoundException');
        } catch (EntityNotFoundException $e) {
        }
        try {
            $this->assertNull($this->query()->where('foo', 'baz')->getOrFail($doc1->_id));
        } catch (EntityNotFoundException $e) {
        }
    }

    public function test_by()
    {
        $this->collection->add($doc1 = (object) ['foo' => 'lorem', 'bar' => 'ipsum']);
        $this->collection->add($doc2 = (object) ['foo' => 'sin', 'bar' => 'dolor']);
        $this->collection->add($doc3 = (object) ['foo' => 'sit', 'bar' => 'amet']);
        $this->collection->add($doc4 = (object) ['foo' => 'sit', 'bar' => 'al']);

        $this->assertEquals(['lorem' => $doc1, 'sin' => $doc2, 'sit' => $doc4], $this->query()->by('foo')->all());
        $this->assertEquals(['lorem' => [$doc1], 'sin' => [$doc2], 'sit' => [$doc3, $doc4]], $this->query()->by('foo', true)->all());
    }

    public function test_scope()
    {
        $this->collection->add($doc1 = (object) ['foo' => 'lorem', 'bar' => 'ipsum']);
        $this->collection->add($doc2 = (object) ['foo' => 'sin', 'bar' => 'dolor']);
        $this->collection->add($doc3 = (object) ['foo' => 'sit', 'bar' => 'amet']);

        $this->assertEquals([$doc1, $doc2], $this->query()->search('r'));
        $this->assertEquals([$doc2, $doc3], $this->query()->search('si'));
        $this->assertEquals([$doc3], $this->query()->where('bar', 'amet')->search('si'));
        $this->assertEquals([$doc2], $this->query()->search('dol'));
    }

    public function test_scope_not_found()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches('#Scope ".*::notFound" not found#');

        $this->query()->notFound();
    }

    private function query(): MongoQuery
    {
        $query = new MongoQuery($this->collection->connection());
        $query->from($this->collection->mapper()->collection());
        $this->extension->apply($query);

        return $query;
    }
}
