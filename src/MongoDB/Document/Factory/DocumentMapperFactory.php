<?php

namespace Bdf\Prime\MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;

/**
 * Base implementation for instantiate a document mapper
 */
final class DocumentMapperFactory implements DocumentMapperFactoryInterface
{
    private DocumentMapperClassResolverInterface $resolver;
    private DocumentMapperIntantiatorInterface $intantiator;

    /**
     * @param DocumentMapperClassResolverInterface|null $resolver Resolver for mapper class name. By default use `SuffixedMapperClassResolver`
     * @param DocumentMapperIntantiatorInterface|null $intantiator Instantiator of the mapper. By defaykt use `DefaultConstructorMapperInstantiator`
     */
    public function __construct(?DocumentMapperClassResolverInterface $resolver = null, ?DocumentMapperIntantiatorInterface $intantiator = null)
    {
        $this->resolver = $resolver ?? new SuffixedMapperClassResolver();
        $this->intantiator = $intantiator ?? new DefaultConstructorMapperInstantiator();
    }

    /**
     * {@inheritdoc}
     */
    public function createByDocumentClassName(string $documentClassName): ?DocumentMapperInterface
    {
        $mapperClass = $this->resolver->resolveByDocumentClass($documentClassName);

        if (!$mapperClass) {
            return null;
        }

        $mapper = $this->intantiator->instantiate($mapperClass);

        return $mapper->forDocument($documentClassName);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function createByMapperClassName(string $mapperClassName, ?string $documentClassName = null): DocumentMapperInterface
    {
        $mapper = $this->intantiator->instantiate($mapperClassName);

        return $mapper->forDocument($documentClassName ?? $this->resolver->resolveDocumentClassByMapperClass($mapperClassName));
    }
}
