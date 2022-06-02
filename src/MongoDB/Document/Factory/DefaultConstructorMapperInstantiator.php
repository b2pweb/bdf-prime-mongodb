<?php

namespace Bdf\Prime\MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;

/**
 * Instantiator using default constructor
 */
final class DefaultConstructorMapperInstantiator implements DocumentMapperIntantiatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function instantiate(string $mapperClassName): DocumentMapperInterface
    {
        return new $mapperClassName();
    }
}
