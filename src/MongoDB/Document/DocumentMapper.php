<?php

namespace Bdf\Prime\MongoDB\Document;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionInterface;
use Bdf\Prime\MongoDB\Document\Hydrator\BdfDocumentHydrator;
use Bdf\Prime\MongoDB\Document\Hydrator\DocumentHydratorFactory;
use Bdf\Prime\MongoDB\Document\Hydrator\DocumentHydratorInterface;
use Bdf\Prime\MongoDB\Document\Hydrator\IdAccessorInterface;
use Bdf\Prime\MongoDB\Document\Hydrator\MongoDocumentIdAccessor;
use Bdf\Prime\MongoDB\Document\Hydrator\ReflectionPropertyIdAccessor;
use Bdf\Prime\MongoDB\Document\Hydrator\StdClassDocumentHydrator;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMapping;
use Bdf\Prime\MongoDB\Document\Mapping\FieldsMappingBuilder;
use Bdf\Prime\MongoDB\Document\Selector\DefaultDocumentSelector;
use Bdf\Prime\MongoDB\Document\Selector\DocumentSelectorInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Schema\CollectionDefinition;
use Bdf\Prime\MongoDB\Schema\CollectionDefinitionBuilder;
use Bdf\Prime\Types\TypesRegistryInterface;
use MongoDB\BSON\ObjectId;
use stdClass;

/**
 * Base implementation of DocumentMapperInterface
 *
 * @template D as object
 * @implements DocumentMapperInterface<D>
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
     * @var DocumentHydratorFactory|null
     */
    private ?DocumentHydratorFactory $hydratorFactory;

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
     * @param class-string<D>|null $documentClass Document class related to the collection. If null stdClass will be used
     */
    public function __construct(?string $documentClass = null, ?DocumentHydratorFactory $hydratorFactory = null)
    {
        $this->documentClass = $documentClass ?? stdClass::class;
        $this->hydratorFactory = $hydratorFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param class-string<R> $documentClassName
     * @psalm-return DocumentMapper<R>
     * @template R as object
     */
    public function forDocument(string $documentClassName): DocumentMapperInterface
    {
        /** @var DocumentMapper<R> $new */
        $new = clone $this;
        $new->documentClass = $documentClassName;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function document(): string
    {
        return $this->documentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function createMongoCollection(MongoConnection $connection): MongoCollectionInterface
    {
        return new MongoCollection($connection, $this);
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
    public function filters(): array
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    final public function definition(): CollectionDefinition
    {
        $builder = new CollectionDefinitionBuilder($this->collection());

        $this->buildDefinition($builder);

        return $builder->build();
    }

    /**
     * Build fields mapping
     *
     * By default, will parse document class using reflection for register declared properties as field
     * Override for declare a custom mapping
     *
     * <code>
     * class MyDocumentMapper extends DocumentMapper
     * {
     *     // ...
     *     public function buildFields(FieldsMappingBuilder $builder): void
     *     {
     *         $builder
     *             ->autoConfigure(MyDocument::class) // Parse fields declared on the document class
     *             ->dateTime('date', DateTimeImmutable::class) // Extra fields can be declared
     *             ->binary('content', Binary::TYPE_GENERIC, 'string') // You can define mapped type : here use a string field as mongo binary
     *         ;
     *     }
     * }
     * </code>
     *
     * @param FieldsMappingBuilder $builder
     * @return void
     */
    protected function buildFields(FieldsMappingBuilder $builder): void
    {
        $builder->autoConfigure($this->documentClass);
    }

    /**
     * Configure the collection definition, like indexes or options
     * Should be overridden for configure
     *
     * <code>
     * class MyDocumentMapper extends DocumentMapper
     * {
     *     // ...
     *     public function buildDefinition(CollectionDefinitionBuilder $builder): void
     *     {
     *         $builder->collation(['locale' => 'fr', 'strength' => 2]); // Declare collection options
     *         $builder->addIndex('idx_search')->on(['name', 'keywords']); // Define indexes
     *         $builder->addIndex('unq_name')->unique()->on(['name', 'type']);
     *
     *         // Can also be declared using closure to keep fluid interface :
     *         $builder
     *             ->collation(['locale' => 'fr', 'strength' => 2])
     *             ->indexes(function (IndexesBuilder $builder) {
     *                 $builder
     *                     ->add('idx_search')->on(['name', 'keywords'])
     *                     ->add('unq_name')->unique()->on(['name', 'type']);
     *             })
     *         ;
     *     }
     * }
     * </code>
     *
     * @param CollectionDefinitionBuilder $builder
     * @return void
     */
    protected function buildDefinition(CollectionDefinitionBuilder $builder): void
    {
        // to overrides
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
        $factory = $this->hydratorFactory ?? DocumentHydratorFactory::instance();

        return $factory->create($documentBaseClass);
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
            /** @var IdAccessorInterface<D> */
            return MongoDocumentIdAccessor::instance();
        }

        return new ReflectionPropertyIdAccessor($documentBaseClass);
    }

    /**
     * Get or create id accessor instance
     *
     * @return IdAccessorInterface<D>
     */
    private function idAccessor(): IdAccessorInterface
    {
        if ($this->idAccessor) {
            return $this->idAccessor;
        }

        return $this->idAccessor = $this->createIdAccessor($this->documentClass);
    }

    /**
     * Get or create hydrator instance
     *
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
     * Get or create selector instance
     *
     * @return DocumentSelectorInterface<D>
     */
    private function selector(): DocumentSelectorInterface
    {
        if ($this->selected) {
            return $this->selected;
        }

        return $this->selected = $this->createDocumentSelector($this->documentClass);
    }
}
