<?php

namespace Bdf\Prime\MongoDB\Document\Factory;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use stdClass;

/**
 * Resolve mapper class with a suffix on the class name
 *
 * The resolved class is the document class name concatenated with the suffix.
 * If no mapper is found with the current document class, the class will be resolved through document ancestors.
 */
final class SuffixedMapperClassResolver implements DocumentMapperClassResolverInterface
{
    public const DEFAULT_SUFFIX = 'Mapper';

    /**
     * @var string
     */
    private string $suffix;

    /**
     * @param string $suffix
     */
    public function __construct(string $suffix = self::DEFAULT_SUFFIX)
    {
        $this->suffix = $suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveByDocumentClass(string $documentClassName): ?string
    {
        $mapperClass = $documentClassName . $this->suffix;

        if (is_subclass_of($mapperClass, DocumentMapperInterface::class)) {
            return $mapperClass;
        }

        foreach (class_parents($documentClassName) as $documentType) {
            $mapperClass = $documentType . $this->suffix;

            if (is_subclass_of($mapperClass, DocumentMapperInterface::class)) {
                return $mapperClass;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDocumentClassByMapperClass(string $mapperClassName): string
    {
        if (!str_ends_with($mapperClassName, $this->suffix)) {
            return stdClass::class;
        }

        $documentClass = substr($mapperClassName, 0, -strlen($this->suffix));

        return class_exists($documentClass) ? $documentClass : stdClass::class;
    }
}
