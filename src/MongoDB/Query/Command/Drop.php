<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * The drop command removes an entire collection from a database
 */
class Drop extends AbstractCommand
{
    /**
     * @var string
     */
    private $collection;


    /**
     * Drop constructor.
     *
     * @param string $collection
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'drop';
    }

    /**
     * {@inheritdoc}
     */
    public function argument()
    {
        return $this->collection;
    }
}
