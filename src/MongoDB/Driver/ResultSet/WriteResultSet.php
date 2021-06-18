<?php

namespace Bdf\Prime\MongoDB\Driver\ResultSet;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use MongoDB\Driver\WriteResult;

/**
 * Adapt mongo WriteResult to ResultSetInterface
 */
final class WriteResultSet extends \EmptyIterator implements ResultSetInterface
{
    /**
     * @var WriteResult
     */
    private $result;


    /**
     * WriteResultSet constructor.
     *
     * @param WriteResult $result
     */
    public function __construct(WriteResult $result)
    {
        $this->result = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->result->getUpsertedIds();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return
            $this->result->getDeletedCount()
            + $this->result->getInsertedCount()
            + $this->result->getModifiedCount()
            + $this->result->getUpsertedCount()
        ;
    }
}
