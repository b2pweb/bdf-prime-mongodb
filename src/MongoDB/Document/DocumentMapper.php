<?php

namespace Bdf\Prime\MongoDB\Document;

use Bdf\Prime\MongoDB\Document\Hydrator\BdfDocumentHydrator;
use Bdf\Prime\MongoDB\Document\Hydrator\DocumentHydratorInterface;
use Bdf\Prime\MongoDB\Document\Hydrator\IdAccessorInterface;
use Bdf\Prime\MongoDB\Document\Hydrator\MongoDocumentIdAccessor;
use Bdf\Prime\MongoDB\Document\Hydrator\ReflectionPropertyIdAccessor;
use Bdf\Prime\MongoDB\Document\Hydrator\StdClassDocumentHydrator;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMapping;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMappingBuilder;
use Bdf\Prime\MongoDB\Document\Selector\DefaultDocumentSelector;
use Bdf\Prime\MongoDB\Document\Selector\DocumentSelectorInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use MongoDB\BSON\ObjectId;
use stdClass;

/**
 * Class DocumentMapper
 *
 * @template D as object
 * @implements DocumentMapperInterface<D>
 *
 * @todo final un peu partout
 * @todo faire une interface et un wrapper pour les hydrators
 */
abstract class DocumentMapper implements DocumentMapperInterface
{
    /**
     * @var class-string<D>
     */
    private string $documentClass;

    /**
     * @var DocumentHydratorInterface|null
     */
    private ?DocumentHydratorInterface $hydrator = null;

    /**
     * @var IdAccessorInterface<D>|null
     */
    private ?IdAccessorInterface $idAccessor = null;

    /**
     * @var DocumentSelectorInterface<D>|null
     */
    private ?DocumentSelectorInterface $selected = null;

    /**
     * @var FieldsMapping|null
     */
    private ?FieldsMapping $fields = null;

    /**
     * @param class-string<D>|null $documentClass
     */
    public function __construct(?string $documentClass = null)
    {
        $this->documentClass = $documentClass ?? substr(static::class, 0, -strlen('Mapper'));
    }

    /**
     * {@inheritdoc}
     */
    public function scopes(): array
    {
        // To overrides
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function queries(): array
    {
        // To overrides
        return [];
    }

    /**
     * {@inheritdoc}
     */
    final public function getId(object $document): ?ObjectId
    {
        return $this->idAccessor()->readId($document);
    }

    /**
     * {@inheritdoc}
     */
    final public function setId(object $document, ?ObjectId $id): void
    {
        $this->idAccessor()->writeId($document, $id);
    }

    /**
     * {@inheritdoc}
     */
    final public function fromDatabase(array $data, TypesRegistryInterface $types): object
    {
        $document = $this->selector()->instantiate($data);
        $data = $this->fields()->fromDatabase($data, $types);

        return $this->hydrator()->fromDatabase($document, $data);
    }

    /**
     * {@inheritdoc}
     */
    final public function toDatabase(object $document, TypesRegistryInterface $types): array
    {
        $data = $this->hydrator()->toDatabase($document);

        return $this->fields()->toDatabase($data, $types);
    }

    /**
     * {@inheritdoc}
     */
    final public function constraints(): array
    {
        return $this->selector()->filters($this->documentClass);
    }

    /**
     * Get declared fields of the handled document
     *
     * @return FieldsMapping
     */
    final public function fields(): FieldsMapping
    {
        if ($this->fields) {
            return $this->fields;
        }

        $this->buildFields($builder = new FieldsMappingBuilder());

        return $this->fields = $builder->build();
    }

    /**
     * @return IdAccessorInterface<D>
     */
    final private function idAccessor(): IdAccessorInterface
    {
        if ($this->idAccessor) {
            return $this->idAccessor;
        }

        return $this->idAccessor = $this->createIdAccessor($this->documentClass);
    }

    /**
     * @return DocumentHydratorInterface
     */
    private function hydrator(): DocumentHydratorInterface
    {
        if ($this->hydrator) {
            return $this->hydrator;
        }

        return $this->hydrator = $this->createHydrator($this->documentClass);
    }

    /**
     * @return DocumentSelectorInterface<D>
     */
    private function selector(): DocumentSelectorInterface
    {
        if ($this->selected) {
            return $this->selected;
        }

        return $this->selected = $this->createDocumentSelector($this->documentClass);
    }

    /**
     * Build fields mapping
     *
     * By default, will parse document class using reflection for register declared properties as field
     * Override for declare a custom mapping
     *
     * @param FieldsMappingBuilder $builder
     * @return void
     */
    protected function buildFields(FieldsMappingBuilder $builder): void
    {
        $builder->autoConfigure($this->documentClass);
    }

    /**
     * Create the document class selector for the current collection
     * Can be overridden for configuration
     *
     * @param class-string<D> $documentBaseClass
     *
     * @return DocumentSelectorInterface<D>
     */
    protected function createDocumentSelector(string $documentBaseClass): DocumentSelectorInterface
    {
        return new DefaultDocumentSelector($documentBaseClass);
    }

    /**
     * Create the document hydrator for the current collection
     * Can be overridden for configuration
     *
     * @param class-string<D> $documentBaseClass
     *
     * @return DocumentHydratorInterface
     */
    protected function createHydrator(string $documentBaseClass): DocumentHydratorInterface
    {
        if ($documentBaseClass === stdClass::class) {
            return StdClassDocumentHydrator::instance();
        }

        return new BdfDocumentHydrator();
    }

    /**
     * Create the id accessor for the handled document type
     * Can be overridden for configuration
     *
     * @param class-string<D> $documentBaseClass
     *
     * @return IdAccessorInterface<D>
     */
    protected function createIdAccessor(string $documentBaseClass): IdAccessorInterface
    {
        if (($hydrator = $this->hydrator()) instanceof IdAccessorInterface) {
            return $hydrator;
        }

        if (is_subclass_of($documentBaseClass, MongoDocument::class)) {
            return MongoDocumentIdAccessor::instance();
        }

        return new ReflectionPropertyIdAccessor($documentBaseClass);
    }
}
