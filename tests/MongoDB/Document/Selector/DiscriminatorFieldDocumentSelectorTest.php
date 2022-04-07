<?php

namespace MongoDB\Document\Selector;

use Bdf\Prime\MongoDB\Document\Selector\DiscriminatorFieldDocumentSelector;
use Bdf\Prime\MongoDB\TestDocument\BarDocument;
use Bdf\Prime\MongoDB\TestDocument\DiscrimiatorDocument;
use Bdf\Prime\MongoDB\TestDocument\FooDocument;
use PHPUnit\Framework\TestCase;

class DiscriminatorFieldDocumentSelectorTest extends TestCase
{
    public function test()
    {
        $selector = new DiscriminatorFieldDocumentSelector(DiscrimiatorDocument::class, [
            'foo' => FooDocument::class,
            'bar' => BarDocument::class,
            'other' => BarDocument::class,
        ]);

        $this->assertEquals(new DiscrimiatorDocument(), $selector->instantiate([]));
        $this->assertEquals(new DiscrimiatorDocument(), $selector->instantiate(['_type' => 'invalid']));
        $this->assertEquals(new FooDocument(), $selector->instantiate(['_type' => 'foo']));
        $this->assertEquals(new BarDocument(), $selector->instantiate(['_type' => 'bar']));
        $this->assertEquals(new BarDocument(), $selector->instantiate(['_type' => 'other']));
        $this->assertEquals(new DiscrimiatorDocument(), $selector->instantiate(['_type' => new \stdClass()]));

        $this->assertEquals([], $selector->filters(DiscrimiatorDocument::class));
        $this->assertEquals(['_type' => 'foo'], $selector->filters(FooDocument::class));
        $this->assertEquals(['_type' => ['$in' => ['bar', 'other']]], $selector->filters(BarDocument::class));
    }
}
