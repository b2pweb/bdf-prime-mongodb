<?php

namespace MongoDB\Document\Selector;

use Bdf\Prime\MongoDB\Document\Selector\DefaultDocumentSelector;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClass;
use PHPUnit\Framework\TestCase;

class DefaultDocumentSelectorTest extends TestCase
{
    public function test()
    {
        $selector = new DefaultDocumentSelector(DocumentWithoutBaseClass::class);

        $this->assertEquals(new DocumentWithoutBaseClass(), $selector->instantiate(['foo' => 'bar']));
        $this->assertEquals([], $selector->filters(DocumentWithoutBaseClass::class));
    }
}
