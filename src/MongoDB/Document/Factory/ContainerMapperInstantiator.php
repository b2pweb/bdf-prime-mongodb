<?php

namespace Bdf\Prime\MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Psr\Container\ContainerInterface;

/**
 * Instantiator using injection container
 */
final class ContainerMapperInstantiator implements DocumentMapperIntantiatorInterface
{
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function instantiate(string $mapperClassName): DocumentMapperInterface
    {
        return $this->container->get($mapperClassName);
    }
}
