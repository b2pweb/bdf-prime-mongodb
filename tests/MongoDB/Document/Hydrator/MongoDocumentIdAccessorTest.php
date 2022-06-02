<?php

namespace MongoDB\Document\Hydrator;

use Bdf\Prime\MongoDB\Document\Hydrator\MongoDocumentIdAccessor;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class MongoDocumentIdAccessorTest extends TestCase
{
    public function test_get_set_id()
    {
        $doc = new class extends MongoDocument {};
        $accessor = new MongoDocumentIdAccessor();

        $this->assertNull($accessor->readId($doc));

        $id = new ObjectId();
        $accessor->writeId($doc, $id);

        $this->assertSame($id, $doc->id());
        $this->assertSame($id, $accessor->readId($doc));

        $accessor->writeId($doc, null);
        $this->assertNull($accessor->readId($doc));
    }

    public function test_instance()
    {
        $this->assertInstanceOf(MongoDocumentIdAccessor::class, MongoDocumentIdAccessor::instance());
        $this->assertSame(MongoDocumentIdAccessor::instance(), MongoDocumentIdAccessor::instance());
    }
}
