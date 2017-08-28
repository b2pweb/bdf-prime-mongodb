<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\Schema\Comparator\IndexSetComparatorInterface;
use Bdf\Prime\Schema\Comparator\ReplaceIndexSetComparator;
use Bdf\Prime\Schema\IndexInterface;
use MongoDB\Driver\Command;

/**
 * Handle diff for indexes on MongoDB.
 * This class will generate mongo commands from an index set comparator
 *
 * @link https://docs.mongodb.com/manual/reference/command/createIndexes/
 * @link https://docs.mongodb.com/manual/reference/command/dropIndexes/
 */
class IndexSetDiff implements CommandSetInterface
{
    /**
     * @var string
     */
    private $collection;

    /**
     * @var IndexSetComparatorInterface
     */
    private $comparator;


    /**
     * IndexSetDiff constructor.
     *
     * @param string $collection
     * @param IndexSetComparatorInterface $comparator
     */
    public function __construct($collection, IndexSetComparatorInterface $comparator)
    {
        $this->collection = $collection;
        $this->comparator = new ReplaceIndexSetComparator($comparator);
    }

    /**
     * @return string
     */
    public function collection()
    {
        return $this->collection;
    }

    /**
     * @return IndexSetComparatorInterface
     */
    public function comparator()
    {
        return $this->comparator;
    }

    /**
     * {@inheritdoc}
     */
    public function commands()
    {
        return array_merge(
            $this->removeCommands(),
            $this->createCommands()
        );
    }

    /**
     * @return Command[]
     */
    protected function removeCommands()
    {
        $removed = $this->filterPrimary(
            $this->comparator->removed()
        );

        $commands = [];

        foreach ($removed as $index) {
            $commands[] = new Command([
                'dropIndexes' => $this->collection,
                'index'       => $index->name()
            ]);
        }

        return $commands;
    }

    /**
     * @return Command[]
     */
    protected function createCommands()
    {
        $added = $this->filterPrimary(
            $this->comparator->added()
        );

        if (empty($added)) {
            return [];
        }

        $indexQuery = [];

        foreach ($added as $index) {
             $data = [
                'key'  => array_fill_keys($index->fields(), 1),
                'name' => $index->name()
            ];

             if ($index->unique()) {
                 $data['unique'] = 1;
             }

            $indexQuery[] = $data;
        }

        return [
            new Command([
                'createIndexes' => $this->collection,
                'indexes'       => $indexQuery
            ])
        ];
    }

    /**
     * Filter primary indexes from index list
     *
     * @param IndexInterface[] $indexes
     *
     * @return IndexInterface[]
     */
    protected function filterPrimary(array $indexes)
    {
        return array_filter($indexes, function (IndexInterface $index) {
            return !$index->primary();
        });
    }
}
