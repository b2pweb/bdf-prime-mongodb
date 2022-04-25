<?php

namespace MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\Factory\DefaultConstructorMapperInstantiator;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClassMapper;
use PHPUnit\Framework\TestCase;

class DefaultConstructorMapperInstantiatorTest extends TestCase
{
    public function test_instantiate()
    {
        $this->assertInstanceOf(DocumentWithoutBaseClassMapper::class, (new DefaultConstructorMapperInstantiator())->instantiate(DocumentWithoutBaseClassMapper::class));
    }
}
