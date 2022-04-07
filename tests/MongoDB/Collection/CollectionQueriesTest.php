<?php

namespace MongoDB\Collection;

use Bdf\Prime\MongoDB\Collection\CollectionQueries;
use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Query\MongoInsertQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\MongoDB\TestDocument\BarDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use PHPUnit\Framework\TestCase;

class CollectionQueriesTest extends TestCase
{
    use PrimeTestCase;

    private CollectionQueries $queries;
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

                public function queries(): array
                {
                    return [
                        'withFields' => function (MongoCollection $collection, string... $fields) {
                            return $collection->findAllRaw(array_fill_keys($fields, ['$exists' => true]));
                        }
                    ];
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

        $this->queries = new CollectionQueries($this->collection, $this->collection->mapper(), $this->collection->connection());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    public function test_query()
    {
        $this->collection->add($doc1 = (object) ['foo' => 'bar']);
        $this->collection->add($doc2 = (object) ['foo' => 'baz']);

        $this->assertEquals([$doc1, $doc2], iterator_to_array($this->queries->query()->all()));
        $this->assertEquals([$doc1], iterator_to_array($this->queries->query()->where('foo', 'bar')->all()));
    }

    public function test_keyValue()
    {
        $this->collection->add($doc1 = (object) ['foo' => 'bar']);
        $this->collection->add($doc2 = (object) ['foo' => 'baz']);

        $this->assertEquals([$doc1, $doc2], iterator_to_array($this->queries->keyValue()->all()));
        $this->assertEquals([$doc1], iterator_to_array($this->queries->keyValue('foo', 'bar')->all()));
    }

    public function test_keyValue_with_constraints_should_return_null()
    {
        $collection = BarDocument::collection();
        $queries = new CollectionQueries($collection, $collection->mapper(), $collection->connection());

        $this->assertNull($queries->keyValue());
    }

    public function test_make()
    {
        $query = $this->queries->make(MongoInsertQuery::class);

        $this->assertInstanceOf(MongoInsertQuery::class, $query);
        $this->assertEquals('test_simple_object', $query->statements['collection']);
    }

    public function test_custom_query()
    {
        $this->collection->add($doc1 = (object) ['foo' => 1, 'bar' => 2]);
        $this->collection->add($doc2 = (object) ['bar' => 3, 'baz' => 4]);
        $this->collection->add($doc3 = (object) ['foo' => 1, 'baz' => 2]);

        $this->assertEquals([$doc1, $doc2], iterator_to_array($this->queries->withFields('bar')));
        $this->assertEquals([$doc1, $doc3], iterator_to_array($this->queries->withFields('foo')));
        $this->assertEquals([$doc1], iterator_to_array($this->queries->withFields('foo', 'bar')));
    }

    public function test_scope()
    {
        $this->collection->add($doc1 = (object) ['foo' => 'lorem', 'bar' => 'ipsum']);
        $this->collection->add($doc2 = (object) ['foo' => 'sin', 'bar' => 'dolor']);
        $this->collection->add($doc3 = (object) ['foo' => 'sit', 'bar' => 'amet']);

        $this->assertEquals([$doc1, $doc2], iterator_to_array($this->queries->search('r')));
        $this->assertEquals([$doc2, $doc3], iterator_to_array($this->queries->search('si')));
        $this->assertEquals([$doc2], iterator_to_array($this->queries->search('dol')));
    }
}
