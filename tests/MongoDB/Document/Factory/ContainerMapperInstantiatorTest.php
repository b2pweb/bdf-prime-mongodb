<?php

namespace MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\Factory\ContainerMapperInstantiator;
use Bdf\Prime\MongoDB\TestDocument\DocumentWithoutBaseClassMapper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerMapperInstantiatorTest extends TestCase
{
    public function test_instantiate()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with(DocumentWithoutBaseClassMapper::class)->willReturn(new DocumentWithoutBaseClassMapper());

        $this->assertInstanceOf(DocumentWithoutBaseClassMapper::class, (new ContainerMapperInstantiator($container))->instantiate(DocumentWithoutBaseClassMapper::class));
    }
}
