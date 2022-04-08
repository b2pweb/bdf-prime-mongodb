<?php

namespace MongoDB\Collection;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\Selector\DiscriminatorFieldDocumentSelector;
use Bdf\Prime\MongoDB\Document\Selector\DocumentSelectorInterface;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

class MongoCollectionWithDiscriminatorTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoCollection<Base>
     */
    private $collection;

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

        $this->collection = new MongoCollection(Prime::connection('mongo'), new DiscrimiatorMapper(Base::class));
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
        $base = new Base(12);
        $foo = new Foo(23, 'aaa');
        $bar = new Bar(34, 10);

        $this->collection->add($base);
        $this->collection->add($foo);
        $this->collection->add($bar);

        $this->assertEquals($base, $this->collection->get($base->id()));
        $this->assertEquals($foo, $this->collection->get($foo->id()));
        $this->assertEquals($bar, $this->collection->get($bar->id()));
    }

    /**
     * @return void
     */
    public function test_replace_with_new_type()
    {
        $base = new Base(12);

        $this->collection->replace($base);
        $this->assertEquals($base, $this->collection->refresh($base));

        $foo = new Foo(12, 'aaa');
        $foo->setId($base->id());

        $this->collection->replace($foo);
        $this->assertEquals($foo, $this->collection->refresh($base));
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $base = new Base(12);
        $base->setId(new ObjectId());
        $foo = new Foo(12, 'aaa');
        $foo->setId($base->id());

        $this->collection->add($base);

        $this->assertTrue($this->collection->exists($base));
        $this->assertTrue($this->collection->exists($foo));

        $this->collection->delete($foo);

        $this->assertFalse($this->collection->exists($base));
        $this->assertFalse($this->collection->exists($foo));
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $base = new Base(12);
        $base->setId(new ObjectId());
        $foo = new Foo(12, 'aaa');
        $foo->setId($base->id());

        $this->collection->add($base);

        $this->collection->update($foo, ['_type', 'foo']);
        $this->assertEquals($foo, $this->collection->refresh($base));
    }

    /**
     * @return void
     */
    public function test_findOneRaw()
    {
        $base = new Base(12);
        $foo = new Foo(23, 'aaa');
        $bar = new Bar(34, 10);

        $this->collection->add($base);
        $this->collection->add($foo);
        $this->collection->add($bar);

        $this->assertEquals($base, $this->collection->findOneRaw(['value' => 12]));
        $this->assertEquals($foo, $this->collection->findOneRaw(['foo' => 'aaa']));
        $this->assertEquals($bar, $this->collection->findOneRaw(['bar' => 10]));
        $this->assertEquals($bar, $this->collection->findOneRaw(['bar' => ['$exists' => true]]));
    }

    /**
     * @return void
     */
    public function test_findAllRaw()
    {
        $base = new Base(12);
        $foo = new Foo(23, 'aaa');
        $bar = new Bar(34, 10);

        $this->collection->add($base);
        $this->collection->add($foo);
        $this->collection->add($bar);

        $this->assertEquals([$base], $this->collection->findAllRaw(['value' => 12]));
        $this->assertEquals([$base, $foo, $bar], $this->collection->findAllRaw(['_type' => ['$exists' => true]]));
        $this->assertEquals([$bar], $this->collection->findAllRaw(['bar' => ['$exists' => true]]));
    }

    /**
     * @return void
     */
    public function test_query()
    {
        $base = new Base(12);
        $foo = new Foo(23, 'aaa');
        $bar = new Bar(34, 10);

        $this->collection->add($base);
        $this->collection->add($foo);
        $this->collection->add($bar);

        $this->assertEquals([$foo, $bar], $this->collection->query()->where('value', '>', 20)->all());
    }

    /**
     * @return void
     */
    public function test_with_subtype_collection()
    {
        $base = new Base(12);
        $foo = new Foo(23, 'aaa');
        $bar = new Bar(34, 10);

        $fooCollection = new MongoCollection(
            Prime::connection('mongo'),
            new DiscrimiatorMapper(Foo::class)
        );

        $this->collection->add($base);
        $this->collection->add($foo);
        $this->collection->add($bar);

        $this->assertNull($fooCollection->refresh($base));
        $this->assertEquals($foo, $fooCollection->refresh($foo));
        $this->assertNull($fooCollection->refresh($bar));

        $this->assertNull($fooCollection->findOneRaw(['value' => 12]));
        $this->assertEquals($foo, $fooCollection->findOneRaw(['foo' => 'aaa']));
        $this->assertNull($fooCollection->findOneRaw(['bar' => 10]));

        $this->assertEquals([$foo], $fooCollection->query()->all());
        $this->assertEquals([$foo], $fooCollection->findAllRaw([]));

        $this->assertEquals(1, $fooCollection->count());
        $this->assertEquals(0, $fooCollection->count(['value' => 123]));

        $this->assertFalse($fooCollection->exists($base));
        $this->assertTrue($fooCollection->exists($foo));
        $this->assertFalse($fooCollection->exists($bar));
    }

    /**
     * @return void
     */
    public function test_with_subtype_collection_with_multiple_discriminator_for_same_class()
    {
        $base = new Base(12);
        $foo = new Foo(23, 'aaa');
        $bar = new Bar(34, 10);
        $babar = new Bar(14, 25);
        $babar->_type = 'babar';

        $barCollection = new MongoCollection(
            Prime::connection('mongo'),
            new DiscrimiatorMapper(Bar::class)
        );

        $this->collection->add($base);
        $this->collection->add($foo);
        $this->collection->add($bar);
        $this->collection->add($babar);

        $this->assertNull($barCollection->refresh($base));
        $this->assertNull($barCollection->refresh($foo));
        $this->assertEquals($bar, $barCollection->refresh($bar));
        $this->assertEquals($babar, $barCollection->refresh($babar));

        $this->assertNull($barCollection->findOneRaw(['value' => 12]));
        $this->assertNull($barCollection->findOneRaw(['foo' => 'aaa']));
        $this->assertEquals($bar, $barCollection->findOneRaw(['bar' => 10]));
        $this->assertEquals($babar, $barCollection->findOneRaw(['value' => 14]));

        $this->assertEquals([$bar, $babar], $barCollection->query()->all());
        $this->assertEquals([$bar, $babar], $barCollection->findAllRaw([]));

        $this->assertEquals(2, $barCollection->count());
        $this->assertEquals(0, $barCollection->count(['value' => 123]));

        $this->assertFalse($barCollection->exists($base));
        $this->assertFalse($barCollection->exists($foo));
        $this->assertTrue($barCollection->exists($bar));
        $this->assertTrue($barCollection->exists($babar));
    }
}

class Base extends MongoDocument
{
    public string $_type = '';
    public ?int $value = null;

    /**
     * @param int|null $value
     */
    public function __construct(?int $value = null)
    {
        $this->value = $value;
    }
}

class Foo extends Base
{
    public string $_type = 'foo';
    public ?string $foo = null;

    public function __construct(?int $value = null, ?string $foo = null)
    {
        parent::__construct($value);

        $this->foo = $foo;
    }
}

class Bar extends Base
{
    public string $_type = 'bar';
    public ?int $bar = null;

    public function __construct(?int $value = null, ?int $bar = null)
    {
        parent::__construct($value);

        $this->bar = $bar;
    }
}

class DiscrimiatorMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'with_discriminator';
    }

    protected function createDocumentSelector(string $documentBaseClass): DocumentSelectorInterface
    {
        return new DiscriminatorFieldDocumentSelector($documentBaseClass, [
            'foo' => Foo::class,
            'bar' => Bar::class,
            'babar' => Bar::class,
        ]);
    }
}
