<?php

namespace MongoDB\Document\Hydrator;

use Bdf\Prime\MongoDB\Document\Hydrator\MongoDocumentIdAccessor;
use Bdf\Prime\MongoDB\Document\Hydrator\ReflectionPropertyIdAccessor;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use LogicException;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class ReflectionPropertyIdAccessorTest extends TestCase
{
    public function test_public()
    {
        $doc = new class {
            public ?ObjectId $_id = null;
        };
        $accessor = new ReflectionPropertyIdAccessor(get_class($doc));

        $this->assertNull($accessor->readId($doc));

        $id = new ObjectId();
        $accessor->writeId($doc, $id);

        $this->assertSame($id, $doc->_id);
        $this->assertSame($id, $accessor->readId($doc));

        $accessor->writeId($doc, null);
        $this->assertNull($accessor->readId($doc));
    }

    public function test_protected()
    {
        $doc = new class {
            protected ?ObjectId $_id = null;

            public function id(): ?ObjectId { return $this->_id; }
        };
        $accessor = new ReflectionPropertyIdAccessor(get_class($doc));

        $this->assertNull($accessor->readId($doc));

        $id = new ObjectId();
        $accessor->writeId($doc, $id);

        $this->assertSame($id, $doc->id());
        $this->assertSame($id, $accessor->readId($doc));

        $accessor->writeId($doc, null);
        $this->assertNull($accessor->readId($doc));
    }

    public function test_private()
    {
        $doc = new class {
            private ?ObjectId $_id = null;

            public function id(): ?ObjectId { return $this->_id; }
        };
        $accessor = new ReflectionPropertyIdAccessor(get_class($doc));

        $this->assertNull($accessor->readId($doc));

        $id = new ObjectId();
        $accessor->writeId($doc, $id);

        $this->assertSame($id, $doc->id());
        $this->assertSame($id, $accessor->readId($doc));

        $accessor->writeId($doc, null);
        $this->assertNull($accessor->readId($doc));
    }

    public function test_private_inherited()
    {
        $doc = new class extends BaseClassWithId {};
        $accessor = new ReflectionPropertyIdAccessor(get_class($doc));

        $this->assertNull($accessor->readId($doc));

        $id = new ObjectId();
        $accessor->writeId($doc, $id);

        $this->assertSame($id, $doc->id());
        $this->assertSame($id, $accessor->readId($doc));

        $accessor->writeId($doc, null);
        $this->assertNull($accessor->readId($doc));
    }

    public function test_property_is_missing()
    {
        $doc = new class {};
        $accessor = new ReflectionPropertyIdAccessor(get_class($doc));

        $this->expectException(LogicException::class);

        $accessor->readId($accessor);
    }
}

class BaseClassWithId
{
    private ?ObjectId $_id = null;

    public function id(): ?ObjectId
    {
        return $this->_id;
    }
}
