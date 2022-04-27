<?php

namespace MongoDB\Document\Hydrator;

use Bdf\Prime\MongoDB\Document\Hydrator\BdfDocumentHydrator;
use Bdf\Prime\MongoDB\Document\Hydrator\DocumentHydratorFactory;
use Bdf\Prime\MongoDB\Document\Hydrator\StdClassDocumentHydrator;
use Bdf\Prime\MongoDB\TestDocument\FooDocument;
use PHPUnit\Framework\TestCase;

class DocumentHydratorFactoryTest extends TestCase
{
    public function test_instance()
    {
        $this->assertInstanceOf(DocumentHydratorFactory::class, DocumentHydratorFactory::instance());
        $this->assertSame(DocumentHydratorFactory::instance(), DocumentHydratorFactory::instance());
    }

    public function test_create()
    {
        $hydrator = new DocumentHydratorFactory();

        $this->assertInstanceOf(StdClassDocumentHydrator::class, $hydrator->create(\stdClass::class));
        $this->assertInstanceOf(BdfDocumentHydrator::class, $hydrator->create(FooDocument::class));

        $this->assertSame($hydrator->create(FooDocument::class), $hydrator->create(FooDocument::class));
    }
}
