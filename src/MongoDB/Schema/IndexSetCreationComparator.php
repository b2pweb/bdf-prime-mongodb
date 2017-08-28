<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\Schema\Comparator\IndexSetComparatorInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\IndexSetInterface;

/**
 * Comparator for create schema indexes.
 * This comparator will only return added(), and do not return the primary index (which will be created automatically)
 */
class IndexSetCreationComparator implements IndexSetComparatorInterface
{
    /**
     * @var IndexSetInterface
     */
    private $indexes;


    /**
     * IndexSetInterfaceCreationComparator constructor.
     *
     * @param IndexSetInterface $indexes
     */
    public function __construct(IndexSetInterface $indexes)
    {
        $this->indexes = $indexes;
    }

    /**
     * {@inheritdoc}
     */
    public function added()
    {
        // Remove primary
        return array_values(
            array_filter(
                $this->indexes->all(),
                function (IndexInterface $index) {
                    return !$index->primary();
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function changed()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function removed()
    {
        return [];
    }
}
