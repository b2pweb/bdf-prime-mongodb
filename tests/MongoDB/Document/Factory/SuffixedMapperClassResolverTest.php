<?php

namespace MongoDB\Document\Factory;

use ArrayObject;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\Factory\SuffixedMapperClassResolver;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocument;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocumentMapper;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClass;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClassMapper;
use Bdf\Prime\MongoDB\TestDocument\FooDocument;
use PHPUnit\Framework\TestCase;
use stdClass;

class SuffixedMapperClassResolverTest extends TestCase
{
    public function test_resolveByDocumentClass()
    {
        $resolver = new SuffixedMapperClassResolver();

        $this->assertSame(DocumentWithoutBaseClassMapper::class, $resolver->resolveByDocumentClass(DocumentWithoutBaseClass::class));
        $this->assertSame(DiscrimiatorDocumentMapper::class, $resolver->resolveByDocumentClass(DiscrimiatorDocument::class));
        $this->assertSame(DiscrimiatorDocumentMapper::class, $resolver->resolveByDocumentClass(FooDocument::class));
        $this->assertNull($resolver->resolveByDocumentClass(ArrayObject::class));
    }

    public function test_resolveDocumentClassByMapperClass()
    {
        $resolver = new SuffixedMapperClassResolver();

        $this->assertSame(DocumentWithoutBaseClass::class, $resolver->resolveDocumentClassByMapperClass(DocumentWithoutBaseClassMapper::class));
        $this->assertSame(DiscrimiatorDocument::class, $resolver->resolveDocumentClassByMapperClass(DiscrimiatorDocumentMapper::class));
        $this->assertSame(stdClass::class, $resolver->resolveDocumentClassByMapperClass(WithoutMatchingDocumentClassMapper::class));
        $this->assertSame(stdClass::class, $resolver->resolveDocumentClassByMapperClass(MapperWithoutSuffix::class));
    }
}

class WithoutMatchingDocumentClassMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'foo';
    }

    public function collection(): string
    {
        return 'foo';
    }
}

class MapperWithoutSuffix extends DocumentMapper
{
    public function connection(): string
    {
        return 'foo';
    }

    public function collection(): string
    {
        return 'foo';
    }
}
