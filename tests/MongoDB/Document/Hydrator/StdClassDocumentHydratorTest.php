<?php

namespace MongoDB\Document\Hydrator;

use Bdf\Prime\MongoDB\Document\Hydrator\MongoDocumentIdAccessor;
use Bdf\Prime\MongoDB\Document\Hydrator\StdClassDocumentHydrator;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class StdClassDocumentHydratorTest extends TestCase
{
    public function test_get_set_id()
    {
        $doc = new \stdClass();
        $accessor = new StdClassDocumentHydrator();

        $this->assertNull($accessor->readId($doc));

        $id = new ObjectId();
        $accessor->writeId($doc, $id);

        $this->assertSame($id, $doc->_id);
        $this->assertSame($id, $accessor->readId($doc));

        $accessor->writeId($doc, null);
        $this->assertNull($accessor->readId($doc));
    }

    /**
     * @return void
     */
    public function test_from_to_database()
    {
        $doc = new \stdClass();
        $hydrator = new StdClassDocumentHydrator();

        $hydrator->fromDatabase($doc, ['foo' => 'bar', 'a' => ['b' => 'c']]);
        $this->assertEquals((object) ['foo' => 'bar', 'a' => ['b' => 'c']], $doc);

        $this->assertSame(
            ['foo' => 'bar', 'a' => ['b' => 'c']],
            $hydrator->toDatabase($doc)
        );
    }

    public function test_instance()
    {
        $this->assertInstanceOf(StdClassDocumentHydrator::class, StdClassDocumentHydrator::instance());
        $this->assertSame(StdClassDocumentHydrator::instance(), StdClassDocumentHydrator::instance());
    }
}
