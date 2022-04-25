<?php

namespace Bdf\Prime\MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;

/**
 * Handle instantiation of document mappers
 */
interface DocumentMapperIntantiatorInterface
{
    /**
     * Instantiate the mapper class
     *
     * @param class-string<M> $mapperClassName
     * @return M
     *
     * @template M as DocumentMapperInterface
     */
    public function instantiate(string $mapperClassName): DocumentMapperInterface;
}
