<?php

namespace MongoDB\Document\Hydrator;

use Bdf\Prime\MongoDB\Document\Hydrator\BdfDocumentHydrator;
use Bdf\Prime\MongoDB\Document\Hydrator\DocumentHydratorInterface;
use DateTime;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\Timestamp;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BdfDocumentHydratorTest extends TestCase
{
    private DocumentHydratorInterface $hydrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hydrator = new BdfDocumentHydrator();
    }

    /**
     * @return void
     */
    public function test_from_to_database_simple()
    {
        $doc = new class {
            private string $foo = 'aaa';
            private int $bar = 123;

            public function foo(): string
            {
                return $this->foo;
            }

            public function bar(): int
            {
                return $this->bar;
            }
        };

        $this->assertSame(
            ['foo' => 'aaa', 'bar' => 123],
            $this->hydrator->toDatabase($doc)
        );

        $this->hydrator->fromDatabase($doc, ['foo' => 'bbb', 'bar' => '145']);

        $this->assertSame('bbb', $doc->foo());
        $this->assertSame(145, $doc->bar());
    }

    /**
     * @return void
     */
    public function test_from_to_database_datetime()
    {
        $doc = new class {
            public \DateTimeInterface $date;
        };

        $doc->date = new DateTime('1254-03-14');

        $this->assertEquals(
            ['date' => new UTCDateTime($doc->date)],
            $this->hydrator->toDatabase($doc)
        );

        $this->hydrator->fromDatabase($doc, ['date' => $newDate = new DateTime('2047-01-05')]);

        $this->assertSame($newDate, $doc->date);
    }

    /**
     * @return void
     */
    public function test_from_to_database_custom_datetime()
    {
//        $this->markTestSkipped('Faut-il le gérer ?');

        $doc = new class {
            public CustomDate $date;
        };

        $doc->date = new CustomDate('1254-03-14');

        $this->assertEquals(
            ['date' => new UTCDateTime($doc->date)],
            $this->hydrator->toDatabase($doc)
        );

        $this->hydrator->fromDatabase($doc, ['date' => $newDate = new CustomDate('2047-01-05')]);

        $this->assertSame($newDate, $doc->date);
    }

    /**
     * @return void
     */
    public function test_from_to_database_bson_types()
    {
        $doc = new class {
            public ObjectId $id;
            public Binary $binary;
            public Decimal128 $decimal;
            public Javascript $javascript;
            public Timestamp $timestamp;
            public UTCDateTime $dateTime;
            public Regex $regex;
        };

        $doc->id = new ObjectId();
        $doc->binary = new Binary('foo', Binary::TYPE_GENERIC);
        $doc->decimal = new Decimal128('125.12365');
        $doc->javascript = new Javascript('function foo() { return "bar"; }');
        $doc->timestamp = new Timestamp(12, 1025);
        $doc->regex = new Regex('.*f.*', 'i');

        $this->assertSame(
            [
                'id' => $doc->id,
                'binary' => $doc->binary,
                'decimal' => $doc->decimal,
                'javascript' => $doc->javascript,
                'timestamp' => $doc->timestamp,
                'regex' => $doc->regex,
            ],
            $this->hydrator->toDatabase($doc)
        );

        $this->hydrator->fromDatabase($doc, [
            'id' => $newId = new ObjectId(),
            'binary' => $newBinary = new Binary('bar', Binary::TYPE_GENERIC),
            'decimal' => $newDecimal = new Decimal128('145.87'),
            'javascript' => $newJavascript = new Javascript('function foo() { return "rab"; }'),
            'timestamp' => $newTimestamp = new Timestamp(47, 1587),
            'regex' => $newRegex = new Regex('bar'),
        ]);

        $this->assertSame($newId, $doc->id);
        $this->assertSame($newBinary, $doc->binary);
        $this->assertSame($newDecimal, $doc->decimal);
        $this->assertSame($newJavascript, $doc->javascript);
        $this->assertSame($newTimestamp, $doc->timestamp);
        $this->assertSame($newRegex, $doc->regex);
    }

    public function test_with_embedded()
    {
        $doc = new class {
            public SubDoc $subDoc;
            /**
             * @var \MongoDB\Document\Hydrator\SubDoc[]
             */
            public array $values;
        };

        $this->hydrator->fromDatabase($doc, $fields = [
            'subDoc' => ['a' => 'aqw', 'b' => 'zsx'],
            'values' => [
                ['a' => 'aze', 'b' => 'rty'],
                ['a' => 'qsd', 'b' => 'fgh'],
            ]
        ]);

        $this->assertSame('aqw', $doc->subDoc->a);
        $this->assertSame('zsx', $doc->subDoc->b);
        $this->assertCount(2, $doc->values);
        $this->assertSame('aze', $doc->values[0]->a);
        $this->assertSame('rty', $doc->values[0]->b);
        $this->assertSame('qsd', $doc->values[1]->a);
        $this->assertSame('fgh', $doc->values[1]->b);

        $this->assertSame($fields, $this->hydrator->toDatabase($doc));
    }
}

class SubDoc
{
    public string $a;
    public string $b;
}

class CustomDate extends DateTime {}
