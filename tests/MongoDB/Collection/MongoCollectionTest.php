<?php

namespace MongoDB\Collection;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\MongoDB\Collection\BulkCollectionWriter;
use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Driver\Exception\MongoDBALException;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class MongoCollectionTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoCollection<\stdClass>
     */
    private $simpleObjectCollection;

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

        $this->simpleObjectCollection = new MongoCollection(
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
            }
        );
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
        $doc = (object) [
            'foo' => 'bar',
            'value' => 124,
        ];

        $this->simpleObjectCollection->add($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->_id);

        $this->assertEquals($doc, $this->simpleObjectCollection->get($doc->_id));
        $this->assertSame(124, $this->simpleObjectCollection->get($doc->_id)->value);
    }

    /**
     * @return void
     */
    public function test_add_with_id()
    {
        $id = new ObjectId();
        $doc = (object) [
            '_id' => $id,
            'foo' => 'bar',
        ];

        $this->simpleObjectCollection->add($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->_id);

        $this->assertEquals($doc, $this->simpleObjectCollection->get($doc->_id));
    }

    /**
     * @return void
     */
    public function test_replace()
    {
        $doc = (object) ['foo' => 'bar'];

        $this->simpleObjectCollection->replace($doc);
        $this->assertInstanceOf(ObjectId::class, $doc->_id);
        $this->assertEquals($doc, $this->simpleObjectCollection->get($doc->_id));

        $doc->foo = 'baz';
        $this->simpleObjectCollection->replace($doc);
        $this->assertEquals($doc, $this->simpleObjectCollection->get($doc->_id));
    }

    /**
     * @return void
     */
    public function test_replace_with_id()
    {
        $id = new ObjectId();
        $doc = (object) ['foo' => 'bar', '_id' => $id];

        $this->simpleObjectCollection->replace($doc);
        $this->assertSame($id, $doc->_id);
        $this->assertEquals($doc, $this->simpleObjectCollection->get($id));
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $doc = (object) ['foo' => 'bar', 'value' => 123];

        $this->simpleObjectCollection->add($doc);
        $doc->foo = 'aaa';
        $doc->value = 741;

        $this->simpleObjectCollection->update($doc, ['value']);
        $this->assertSame('bar', $this->simpleObjectCollection->get($doc->_id)->foo);
        $this->assertSame(741, $this->simpleObjectCollection->get($doc->_id)->value);
    }

    /**
     * @return void
     */
    public function test_update_all_fields()
    {
        $doc = (object) ['foo' => 'bar', 'value' => 123];

        $this->simpleObjectCollection->add($doc);
        $doc->foo = 'aaa';
        $doc->value = 741;

        $this->simpleObjectCollection->update($doc);
        $this->assertSame('aaa', $this->simpleObjectCollection->get($doc->_id)->foo);
        $this->assertSame(741, $this->simpleObjectCollection->get($doc->_id)->value);
    }

    /**
     * @return void
     */
    public function test_update_without_id_should_fail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->simpleObjectCollection->update((object) ['foo' => 'bar', 'value' => 123]);
    }

    /**
     * @return void
     */
    public function test_delete_without_id()
    {
        $doc = (object) ['foo' => 'bar'];

        $this->simpleObjectCollection->delete($doc);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $doc = (object) ['foo' => 'bar', '_id' => new ObjectId()];

        $this->simpleObjectCollection->add($doc);
        $this->simpleObjectCollection->delete($doc);

        $this->assertNull($this->simpleObjectCollection->get($doc->_id));
        $this->simpleObjectCollection->delete($doc);
    }

    /**
     * @return void
     */
    public function test_delete_by_id()
    {
        $doc = (object) ['foo' => 'bar', '_id' => new ObjectId()];

        $this->simpleObjectCollection->add($doc);
        $this->simpleObjectCollection->delete($doc->_id);

        $this->assertNull($this->simpleObjectCollection->get($doc->_id));
        $this->simpleObjectCollection->delete($doc->_id);
    }

    /**
     * @return void
     */
    public function test_findOneRaw()
    {
        $doc1 = (object) ['foo' => 'bar'];
        $doc2 = (object) ['foo' => 'baz'];
        $doc3 = (object) ['foo' => 'rab', 'value' => 123];

        $this->simpleObjectCollection->add($doc1);
        $this->simpleObjectCollection->add($doc2);
        $this->simpleObjectCollection->add($doc3);

        $this->assertEquals($doc1, $this->simpleObjectCollection->findOneRaw(['foo' => 'bar']));
        $this->assertEquals($doc2, $this->simpleObjectCollection->findOneRaw(['foo' => ['$regex' => '.*z']]));
        $this->assertEquals($doc3, $this->simpleObjectCollection->findOneRaw(['value' => ['$exists' => true]]));
        $this->assertNull($this->simpleObjectCollection->findOneRaw(['not' => 'found']));
    }

    /**
     * @return void
     */
    public function test_findAllRaw()
    {
        $doc1 = (object) ['foo' => 'bar'];
        $doc2 = (object) ['foo' => 'baz'];
        $doc3 = (object) ['foo' => 'rab', 'value' => 123];

        $this->simpleObjectCollection->add($doc1);
        $this->simpleObjectCollection->add($doc2);
        $this->simpleObjectCollection->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], $this->simpleObjectCollection->findAllRaw(['foo' => 'bar']));
        $this->assertEqualsCanonicalizing([$doc1, $doc2], $this->simpleObjectCollection->findAllRaw(['foo' => ['$regex' => '^b.*']]));
        $this->assertEquals([$doc3], $this->simpleObjectCollection->findAllRaw(['value' => ['$gt' => 10]]));
        $this->assertEqualsCanonicalizing([], $this->simpleObjectCollection->findAllRaw(['value' => 'bob']));
    }

    /**
     * @return void
     */
    public function test_query()
    {
        $doc1 = (object) ['foo' => 'bar'];
        $doc2 = (object) ['foo' => 'baz'];
        $doc3 = (object) ['foo' => 'rab', 'value' => 123];

        $this->simpleObjectCollection->add($doc1);
        $this->simpleObjectCollection->add($doc2);
        $this->simpleObjectCollection->add($doc3);

        $this->assertEqualsCanonicalizing([$doc1], $this->simpleObjectCollection->query()->where('foo', 'bar')->all());
        $this->assertEqualsCanonicalizing([$doc1, $doc2], $this->simpleObjectCollection->query()->where('foo', (new Like('b'))->startsWith())->all());
        $this->assertEquals([$doc3], $this->simpleObjectCollection->query()->whereRaw(['value' => ['$exists' => true]])->all());
        $this->assertEquals([], $this->simpleObjectCollection->query()->whereRaw(['foo' => ['$exists' => false]])->all());
    }

    /**
     * @return void
     */
    public function test_count()
    {
        $doc1 = (object) ['foo' => 'bar'];
        $doc2 = (object) ['foo' => 'baz'];
        $doc3 = (object) ['foo' => 'rab', 'value' => 123];

        $this->simpleObjectCollection->add($doc1);
        $this->simpleObjectCollection->add($doc2);
        $this->simpleObjectCollection->add($doc3);

        $this->assertEquals(3, $this->simpleObjectCollection->count());
        $this->assertEquals(1, $this->simpleObjectCollection->count(['foo' => 'bar']));
        $this->assertEquals(2, $this->simpleObjectCollection->count(['foo :like' => 'b%']));
    }

    /**
     * @return void
     */
    public function test_exists()
    {
        $doc = (object) ['foo' => 'bar'];

        $this->assertFalse($this->simpleObjectCollection->exists($doc));

        $this->simpleObjectCollection->add($doc);
        $this->assertTrue($this->simpleObjectCollection->exists($doc));

        $this->simpleObjectCollection->delete($doc);
        $this->assertFalse($this->simpleObjectCollection->exists($doc));
    }

    /**
     * @return void
     */
    public function test_refresh()
    {
        $doc = (object) ['foo' => 'bar'];

        $this->assertNull($this->simpleObjectCollection->refresh($doc));

        $this->simpleObjectCollection->add($doc);
        $this->assertEquals($doc, $this->simpleObjectCollection->refresh($doc));
        $this->assertNotSame($doc, $this->simpleObjectCollection->refresh($doc));

        $this->simpleObjectCollection->delete($doc);
        $this->assertNull($this->simpleObjectCollection->refresh($doc));

        $this->simpleObjectCollection->add((object) ['_id' => $doc->_id, 'foo' => 'other']);
        $this->assertEquals((object) ['_id' => $doc->_id, 'foo' => 'other'], $this->simpleObjectCollection->refresh($doc));
        $this->assertEquals('bar', $doc->foo);
    }

    public function test_writer()
    {
        $this->assertInstanceOf(BulkCollectionWriter::class, $this->simpleObjectCollection->writer());
        $this->assertNotSame($this->simpleObjectCollection->writer(), $this->simpleObjectCollection->writer());

        $writer = $this->simpleObjectCollection->writer();

        $writer->insert($doc1 = (object) ['foo' => 'bar']);
        $writer->insert($doc2 = (object) ['foo' => 'baz']);

        $this->assertInstanceOf(ObjectId::class, $doc1->_id);
        $this->assertInstanceOf(ObjectId::class, $doc2->_id);

        $this->assertFalse($this->simpleObjectCollection->exists($doc1));
        $this->assertFalse($this->simpleObjectCollection->exists($doc2));

        $writer->flush();

        $this->assertTrue($this->simpleObjectCollection->exists($doc1));
        $this->assertTrue($this->simpleObjectCollection->exists($doc2));
    }

    public function test_write_exceptions()
    {
        $this->assertThrows(MongoDBALException::class, function () { $this->simpleObjectCollection->insert(['_id' => []]); });
        $this->assertThrows(MongoDBALException::class, function () { $this->simpleObjectCollection->update((object) ['_id' => new ObjectId(), '.$$$[]' => '$$$']); });
    }

    private function assertThrows(string $exceptionClass, callable $task): void
    {
        try {
            $task();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($exceptionClass, $e);
            return;
        }

        $this->fail('Expect ' . $exceptionClass . ' to be thrown');
    }
}
