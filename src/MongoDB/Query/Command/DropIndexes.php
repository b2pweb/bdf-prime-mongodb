<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * The dropIndexes command drops one or all indexes from the current collection
 *
 * @link https://docs.mongodb.com/v3.4/reference/command/dropIndexes/
 */
class DropIndexes extends AbstractCommand
{
    /**
     * @var string
     */
    private $collection;

    /**
     * @var string
     */
    private $index;


    /**
     * DropIndexes constructor.
     *
     * @param string $collection
     * @param string $index
     */
    public function __construct($collection, $index = '*')
    {
        $this->collection = $collection;
        $this->index = $index;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'dropIndexes';
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return parent::document() + ['index' => $this->index];
    }

    /**
     * {@inheritdoc}
     */
    protected function argument()
    {
        return $this->collection;
    }

    /**
     * Drop one index by its name
     *
     * @param string $name
     *
     * @return $this
     */
    public function index($name)
    {
        $this->index = $name;
        return $this;
    }

    /**
     * Drop all indexes of the collection
     *
     * @return DropIndexes
     */
    public function all()
    {
        return $this->index('*');
    }
}
