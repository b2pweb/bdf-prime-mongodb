<?php

namespace MongoDB\Collection;

use Bdf\Prime\MongoDB\Collection\CollectionPreprocessor;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Platform\Types\BsonDateType;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoInsertQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\MongoDB\TestDocument\BarDocument;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Query\Expression\TypedExpressionInterface;
use Bdf\Prime\Types\TypeInterface;
use DateTime;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CollectionPreprocessorTest extends TestCase
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

    /**
     * @return void
     */
    public function test_field_not_found()
    {
        $type = true;
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());

        $this->assertSame('not_found', $preprocessor->field('not_found', $type));
        $this->assertNull($type);
    }

    /**
     * @return void
     */
    public function test_field_with_type()
    {
        $type = true;
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());

        $this->assertSame('e1.date', $preprocessor->field('e1.date', $type));
        $this->assertInstanceOf(BsonDateType::class, $type);
    }

    /**
     *
     */
    public function test_expression_column_value()
    {
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());
        $this->assertEquals([
            'column' => 'e1.date',
            'value' => new UTCDateTime(new DateTime('1234-12-23')),
            'converted' => true
        ], $preprocessor->expression([
            'column' => 'e1.date',
            'value' => new DateTime('1234-12-23')
        ]));
    }

    /**
     *
     */
    public function test_expression_array_value()
    {
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());
        $this->assertEquals([
            'column' => 'e1.date',
            'value' => [new UTCDateTime(new DateTime('1234-12-23')), new UTCDateTime(new DateTime('2145-05-15'))],
            'converted' => true
        ], $preprocessor->expression([
            'column' => 'e1.date',
            'value' => [new DateTime('1234-12-23'), new DateTime('2145-05-15')]
        ]));
    }

    /**
     *
     */
    public function test_expression_raw()
    {
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());
        $expression = [
            'raw' => new Raw('sss')
        ];

        $this->assertSame($expression, $preprocessor->expression($expression));
    }

    /**
     *
     */
    public function test_expression_with_typed_expression_will_setType()
    {
        $typed = $this->createMock(TypedExpressionInterface::class);
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());

        $expression = [
            'column' => 'e1.date',
            'operator' => 'in',
            'value' => $typed,
            'converted' => true
        ];

        $typed->expects($this->once())
            ->method('setType')
            ->with(DocumentWithEmbeddedDate::collection()->connection()->platform()->types()->get(TypeInterface::DATETIME))
        ;

        $this->assertEquals([
            'column' => 'e1.date',
            'operator' => 'in',
            'value' => $typed,
            'converted' => true
        ], $preprocessor->expression($expression));
    }

    public function test_table()
    {
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());

        $this->assertSame(['table' => 'foo'], $preprocessor->table(['table' => 'foo']));
    }

    public function test_root()
    {
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());
        $this->assertNull($preprocessor->root());
    }

    public function test_forInsert()
    {
        $query = new MongoInsertQuery(DocumentWithEmbeddedDate::connection());
        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());

        $this->assertNotSame($query, $preprocessor->forInsert($query));
        $this->assertEquals($query, $preprocessor->forInsert($query));
    }

    public function test_forUpdate()
    {
        $query = new class(DocumentWithEmbeddedDate::connection()) extends MongoQuery {
            public function __construct(MongoConnection $connection, PreprocessorInterface $preprocessor = null)
            {
                parent::__construct($connection, $preprocessor);
                $this->setType(self::TYPE_UPDATE);
            }
        };

        $preprocessor = new CollectionPreprocessor(DocumentWithEmbeddedDate::collection());

        $this->assertNotSame($query, $preprocessor->forUpdate($query));
        $this->assertEquals($query, $preprocessor->forUpdate($query));
    }

    public function test_forUpdate_with_constraint()
    {
        $query = new class(BarDocument::connection()) extends MongoQuery {
            public function __construct(MongoConnection $connection, PreprocessorInterface $preprocessor = null)
            {
                parent::__construct($connection, $preprocessor);
                $this->setType(self::TYPE_UPDATE);
            }
        };

        $preprocessor = new CollectionPreprocessor(BarDocument::collection());

        $toCompile = $preprocessor->forUpdate($query);

        $this->assertNotSame($query, $toCompile);
        $this->assertEquals($query->whereRaw(['_type' => 'bar']), $toCompile);
    }

    public function test_forDelete_with_constraints()
    {
        $query = new class(BarDocument::connection()) extends MongoQuery {
            public function __construct(MongoConnection $connection, PreprocessorInterface $preprocessor = null)
            {
                parent::__construct($connection, $preprocessor);
                $this->setType(self::TYPE_DELETE);
            }
        };

        $preprocessor = new CollectionPreprocessor(BarDocument::collection());
        $toCompile = $preprocessor->forDelete($query);

        $this->assertNotSame($query, $toCompile);
        $this->assertEquals($query->whereRaw(['_type' => 'bar']), $toCompile);
    }

    public function test_functional_query_should_convert_datetime()
    {
        $doc1 = new DocumentWithEmbeddedDate(
            new EmbeddedWithDate(
                new DateTime('2015-05-04'),
                new OtherEmbedded('foo', 12),
            ),
            new EmbeddedWithDate(
                new DateTime('2002-01-02'),
                new OtherEmbedded('oof', 21),
            ),
        );
        $doc2 = new DocumentWithEmbeddedDate(
            new EmbeddedWithDate(
                new DateTime('2009-05-02'),
                new OtherEmbedded('bar', 41),
            ),
            new EmbeddedWithDate(
                new DateTime('2011-05-02'),
                new OtherEmbedded('baz', 25),
            ),
        );

        $doc1->save();
        $doc2->save();

        $query = DocumentWithEmbeddedDate::where('e1.date', '>', new DateTime('2010-02-01'));
        $this->assertEquals([$doc1], iterator_to_array($query->all()));

        $this->assertEquals(new ReadQuery('DocumentWithEmbeddedDate', [
            'e1.date' => ['$gt' => new UTCDateTime(new DateTime('2010-02-01'))],
        ]), $query->compile());
    }

    public function test_functional_insert_query_should_convert_datetime()
    {
        $doc1 = new DocumentWithEmbeddedDate(
            new EmbeddedWithDate(
                new DateTime('2015-05-04'),
                new OtherEmbedded('foo', 12),
            ),
            new EmbeddedWithDate(
                new DateTime('2002-01-02'),
                new OtherEmbedded('oof', 21),
            ),
        );

        $query = new MongoInsertQuery(DocumentWithEmbeddedDate::collection()->connection(), new CollectionPreprocessor(DocumentWithEmbeddedDate::collection()));
        $query
            ->from(DocumentWithEmbeddedDate::collection()->mapper()->collection())
            ->flatten(false)
            ->values([
                'e1' => [
                    'date' => new DateTime('2015-05-04'),
                    'other' => ['a' => 'foo', 'b' => 12]
                ],
                'e2' => [
                    'date' => new DateTime('2002-01-02'),
                    'other' => ['a' => 'oof', 'b' => 21]
                ],
            ])
        ;

        $expectedQuery = new WriteQuery(DocumentWithEmbeddedDate::collection()->mapper()->collection());
        $expectedQuery->insert([
            'e1' => [
                'date' => new UTCDateTime(new DateTime('2015-05-04')),
                'other' => ['a' => 'foo', 'b' => 12]
            ],
            'e2' => [
                'date' => new UTCDateTime(new DateTime('2002-01-02')),
                'other' => ['a' => 'oof', 'b' => 21]
            ],
        ]);

        $this->assertEquals($expectedQuery, $query->compile());

        $this->assertEquals(1, $query->execute()->count());

        $saved = DocumentWithEmbeddedDate::first();
        $doc1->setId($saved->id());

        $this->assertEquals($doc1, $saved);
    }

    public function test_functional_update_should_convert_datetime()
    {
        $doc = new DocumentWithEmbeddedDate(
            new EmbeddedWithDate(
                new DateTime('2015-05-04'),
                new OtherEmbedded('foo', 12),
            ),
            new EmbeddedWithDate(
                new DateTime('2002-01-02'),
                new OtherEmbedded('oof', 21),
            ),
        );

        $doc->save();

        $query = DocumentWithEmbeddedDate::where('e1.date', '>', new DateTime('2010-02-01'));
        $query
            ->setValue('e1.date', new DateTime('2010-02-10'))
            ->setValue('e1.other.b', 14)
        ;

        $this->assertEquals(1, $query->update());

        $this->assertEquals(new DateTime('2010-02-10'), DocumentWithEmbeddedDate::refresh($doc)->e1->date);
        $this->assertEquals(14, DocumentWithEmbeddedDate::refresh($doc)->e1->other->b);
    }
}

class DocumentWithEmbeddedDate extends MongoDocument
{
    public ?EmbeddedWithDate $e1;
    public ?EmbeddedWithDate $e2;

    /**
     * @param EmbeddedWithDate|null $e1
     * @param EmbeddedWithDate|null $e2
     */
    public function __construct(?EmbeddedWithDate $e1 = null, ?EmbeddedWithDate $e2 = null)
    {
        $this->e1 = $e1;
        $this->e2 = $e2;
    }
}

class EmbeddedWithDate
{
    public ?DateTime $date;
    public ?OtherEmbedded $other;

    /**
     * @param DateTime|null $date
     * @param OtherEmbedded|null $other
     */
    public function __construct(?DateTime $date = null, ?OtherEmbedded $other = null)
    {
        $this->date = $date;
        $this->other = $other;
    }
}

class OtherEmbedded
{
    public ?string $a;
    public ?int $b;

    /**
     * @param string|null $a
     * @param int|null $b
     */
    public function __construct(?string $a = null, ?int $b = null)
    {
        $this->a = $a;
        $this->b = $b;
    }
}

class DocumentWithEmbeddedDateMapper extends DocumentMapper
{
    /**
     * {@inheritdoc}
     */
    public function connection(): string
    {
        return 'mongo';
    }

    /**
     * {@inheritdoc}
     */
    public function collection(): string
    {
        return 'DocumentWithEmbeddedDate';
    }
}
