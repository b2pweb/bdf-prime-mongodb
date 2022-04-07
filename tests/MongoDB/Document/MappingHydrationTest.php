<?php

namespace MongoDB\Document;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMappingBuilder;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use DateTime;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

class MappingHydrationTest extends TestCase
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

        Mongo::configure($locator = new MongoCollectionLocator(Prime::service()->connections()));
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    public function test_with_raw_mongo_types()
    {
        $doc = new class extends MongoDocument {
            public Binary $data;
            public Regex $regex;
            public Javascript $javascript;
        };

        $doc->data = new Binary('foo', Binary::TYPE_GENERIC);
        $doc->regex = new Regex('fo+', 'i');
        $doc->javascript = new Javascript('a + b', ['a' => 12, 'b' => 23]);

        $collection = new MongoCollection(
            Prime::connection('mongo'),
            new class(get_class($doc)) extends DocumentMapper {
                public function connection(): string { return 'mongo'; }
                public function collection(): string { return 'with_raw_types'; }
            }
        );

        $collection->add($doc);

        $this->assertEquals($doc, $collection->refresh($doc));
        $this->assertSame('foo', $collection->refresh($doc)->data->getData());
        $this->assertSame('fo+', $collection->refresh($doc)->regex->getPattern());
        $this->assertSame('a + b', $collection->refresh($doc)->javascript->getCode());
        $this->assertEquals((object) ['a' => 12, 'b' => 23], $collection->refresh($doc)->javascript->getScope());
    }

    public function test_with_mapped_fields()
    {
        $doc = new class extends MongoDocument {
            public string $data;
            public \DateTimeInterface $date;
        };

        $doc->data = 'foo';
        $doc->date = new DateTime('2022-12-01 15:25');

        $collection = new MongoCollection(
            Prime::connection('mongo'),
            new class(get_class($doc)) extends DocumentMapper {
                public function connection(): string { return 'mongo'; }
                public function collection(): string { return 'with_mapped_fields'; }
                protected function buildFields(FieldsMappingBuilder $builder): void
                {
                    $builder
                        ->dateTime('date')
                        ->binary('data')
                    ;
                }
            }
        );

        $collection->add($doc);

        $this->assertEquals($doc, $collection->refresh($doc));

        $rawData = $collection->query()->execute()->all()[0];

        $this->assertInstanceOf(Binary::class, $rawData['data']);
        $this->assertEquals(Binary::TYPE_GENERIC, $rawData['data']->getType());
        $this->assertInstanceOf(UTCDateTime::class, $rawData['date']);
    }

    public function test_with_embedded_doc()
    {
        $doc = new DocumentWithEmbedded();

        $doc->e1 = new EmbeddedWithDate();
        $doc->e1->date = new DateTime('2010-02-03');
        $doc->e1->other = new OtherEmbedded();
        $doc->e1->other->a = 'foo';
        $doc->e1->other->b = 42;
        $doc->e2 = new EmbeddedWithDate();
        $doc->e2->date = new DateTime('2015-01-02');
        $doc->e2->other = new OtherEmbedded();
        $doc->e2->other->a = 'bar';
        $doc->e2->other->b = 22;

        $collection = new MongoCollection(
            Prime::connection('mongo'),
            new class(DocumentWithEmbedded::class) extends DocumentMapper {
                public function connection(): string { return 'mongo'; }
                public function collection(): string { return 'with_embedded'; }
            }
        );

        $collection->add($doc);

        $this->assertEquals($doc, $collection->refresh($doc));

        $rawData = $collection->query()->execute()->asRawArray()->all()[0];

        $this->assertEquals([
            '_id' => $doc->id(),
            'e1' => [
                'date' => new UTCDateTime($doc->e1->date),
                'other' => ['a' => 'foo', 'b' => 42],
            ],
            'e2' => [
                'date' => new UTCDateTime($doc->e2->date),
                'other' => ['a' => 'bar', 'b' => 22],
            ],
        ], $rawData);
    }

    public function test_stdClass_with_mapping()
    {
        $collection = new MongoCollection(
            Prime::connection('mongo'),
            new class(\stdClass::class) extends DocumentMapper {
                public function connection(): string { return 'mongo'; }
                public function collection(): string { return 'std_class'; }
                protected function buildFields(FieldsMappingBuilder $builder): void
                {
                    $builder
                        ->dateTime('date', DateTime::class)
                        ->binary('value', Binary::TYPE_GENERIC, 'string')
                    ;
                }
            }
        );

        $doc = (object) [
            'date' => new DateTime('2010-05-23'),
            'value' => 'foo',
        ];
        $collection->add($doc);

        $this->assertEquals($doc, $collection->refresh($doc));
        $this->assertEquals([[
            '_id' => $doc->_id,
            'date' => new UTCDateTime($doc->date),
            'value' => new Binary('foo', Binary::TYPE_GENERIC),
        ]],$collection->query()->execute()->asRawArray()->all());
    }
}

class DocumentWithEmbedded extends MongoDocument
{
    public ?EmbeddedWithDate $e1;
    public ?EmbeddedWithDate $e2;
}

class EmbeddedWithDate
{
    public ?DateTime $date;
    public ?OtherEmbedded $other;
}

class OtherEmbedded
{
    public ?string $a;
    public ?int $b;
}
