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
        $container = new class implements ContainerInterface {
            public function get(string $id)
            {
                if ($id === DocumentWithoutBaseClassMapper::class) {
                    return new DocumentWithoutBaseClassMapper();
                }
                return null;
            }

            public function has(string $id)
            {
                return true;
            }
        };

        $this->assertInstanceOf(DocumentWithoutBaseClassMapper::class, (new ContainerMapperInstantiator($container))->instantiate(DocumentWithoutBaseClassMapper::class));
    }
}
