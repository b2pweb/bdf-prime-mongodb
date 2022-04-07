<?php

namespace MongoDB\Collection;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

class WithEmbeddedTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MongoCollection<Person>
     */
    private $customClassDocument;

    /**
     * @var MongoCollection<Person>
     */
    private $simpleObjectDocument;

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

        $this->simpleObjectDocument = new MongoCollection(Prime::connection('mongo'), new class(\stdClass::class) extends DocumentMapper {
            public function connection(): string
            {
                return 'mongo';
            }

            public function collection(): string
            {
                return 'simple';
            }
        });
        $this->customClassDocument = new MongoCollection(Prime::connection('mongo'), new DocumentWithEmbeddedMapper());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    public function test_with_stdClass()
    {
        $doc = (object) [
            'aaa' => ['bbb' => 'ccc'],
            'ddd' => [['a' => 1], ['b' => 1]],
        ];

        $this->simpleObjectDocument->add($doc);
        $this->assertEquals($doc, $this->simpleObjectDocument->get($doc->_id));

        $doc->aaa['bbb'] = 'ddd';
        $this->simpleObjectDocument->replace($doc);
        $this->assertEquals($doc, $this->simpleObjectDocument->get($doc->_id));

        $doc->aaa['bbb'] = 'aaa';
        $doc->ddd[0]['a'] = 2;

        $this->simpleObjectDocument->update($doc, ['aaa.bbb']);
        $updatedDoc = $this->simpleObjectDocument->get($doc->_id);

        $this->assertSame('aaa', $updatedDoc->aaa['bbb']);
        $this->assertSame(1, $updatedDoc->ddd[0]['a']);
    }

    public function test_with_class()
    {
        $doc = new DocumentWithEmbedded();
        $doc->foo = 'aaa';
        $doc->bar = new B();
        $doc->bar->bar = 'bbb';
        $doc->bar->baz = 123;
        $doc->list = [new Baz('q', 's'), new Baz('d', 'f')];

        $this->customClassDocument->add($doc);
        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));

        $doc->bar->baz = 159;
        $this->customClassDocument->replace($doc);
        $this->assertEquals($doc, $this->customClassDocument->get($doc->id()));

        $doc->bar->baz = 741;
        $doc->bar->bar = 'aqw';

        $this->customClassDocument->update($doc, ['bar.baz']);
        $updatedDoc = $this->customClassDocument->get($doc->id());

        $this->assertSame(741, $updatedDoc->bar->baz);
        $this->assertSame('bbb', $updatedDoc->bar->bar);
    }
}

class DocumentWithEmbedded extends MongoDocument
{
    public ?string $foo = null;
    public ?B $bar = null;

    /**
     * @var Baz[]
     */
    public array $list = [];
}

class B
{
    public string $bar;
    public int $baz;
}

class Baz
{
    public ?string $a;
    public ?string $b;

    /**
     * @param string|null $a
     * @param string|null $b
     */
    public function __construct(?string $a = null, ?string $b = null)
    {
        $this->a = $a;
        $this->b = $b;
    }
}

class DocumentWithEmbeddedMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'embeddeds';
    }
}
