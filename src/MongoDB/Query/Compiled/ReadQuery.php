<?php

namespace Bdf\Prime\MongoDB\Query\Compiled;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use Bdf\Prime\MongoDB\Query\SelfExecutable;
use MongoDB\Driver\Query;

/**
 * Compiled read query
 */
final class ReadQuery implements SelfExecutable
{
    /**
     * @var string
     */
    private $collection;

    /**
     * @var array
     */
    private $filter;

    /**
     * @var array
     */
    private $options;


    /**
     * ReadQuery constructor.
     *
     * @param string $collection The target collection
     * @param array $filter The criteria filters
     * @param array $options Query options (sort, limit, projection...)
     */
    public function __construct($collection, array $filter = [], array $options = [])
    {
        $this->collection = $collection;
        $this->filter = $filter;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(MongoConnection $connection)
    {
        return new CursorResultSet($connection->executeSelect($this->collection, $this->native()));
    }

    /**
     * Create the native Query object
     *
     * @return Query
     */
    public function native()
    {
        return new Query($this->filter, $this->options);
    }
}
