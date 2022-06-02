<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\MongoDB\Query\Command\CollectionOptionsTrait;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\IndexInterface;

/**
 * Build collection options and indexes
 */
class CollectionDefinitionBuilder
{
    use CollectionOptionsTrait;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var IndexBuilder
     */
    private IndexBuilder $indexBuilder;

    /**
     * @param string $name Collection name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->indexBuilder = new IndexBuilder();
    }

    /**
     * Build indexes using configurator callback
     *
     * <code>
     * $builder->indexes(function (IndexBuilder $builder) {
     *     $builder
     *         ->add('my_index')->on('username')->unique()
     *         ->add()->on(['date' => ['order' => 'DESC'], 'type' => ['length' => 3])
     *     ;
     * });
     * </code>
     *
     * @param callable(IndexBuilder):void $configurator
     * @return $this
     */
    public function indexes(callable $configurator): self
    {
        $configurator($this->indexBuilder);
        return $this;
    }

    /**
     * Add an index to the collection
     *
     * Note: this method return an IndexBuilder instance, and not this, so it will break fluid interface
     *
     * <code>
     * $builder->addIndex('my_index')->on('foo');
     * </code>
     *
     * @param string|null $name The index name. Can be null to generate a name
     *
     * @return IndexBuilder
     */
    public function addIndex(?string $name = null): IndexBuilder
    {
        return $this->indexBuilder->add($name);
    }

    /**
     * Build the definition object
     *
     * @return CollectionDefinition
     */
    public function build(): CollectionDefinition
    {
        $indexes = [
            '_id' => new Index(['_id' => []], Index::TYPE_PRIMARY, '_id_'),
        ];

        // @todo refactor avec prime
        foreach ($this->indexBuilder->build() as $name => $meta) {
            $fields = $meta['fields'];
            unset($meta['fields']);

            $type = IndexInterface::TYPE_SIMPLE;

            if (!empty($meta['unique'])) {
                $type = IndexInterface::TYPE_UNIQUE;
                unset($meta['unique']);
            }

            $index = new NamedIndex(
                new Index($fields, $type, $name, $meta),
                $this->name
            );

            $indexes[$index->name()] = $index;
        }

        return new CollectionDefinition($this->name, new IndexSet($indexes), $this->options);
    }
}
