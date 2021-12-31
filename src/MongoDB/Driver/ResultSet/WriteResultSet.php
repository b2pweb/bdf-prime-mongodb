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
        return $this;
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
    public function asAssociative(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asList(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asClass(string $className): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asColumn(int $column = 0): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asObject(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isRead(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isWrite(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasWrite(): bool
    {
        return $this->count() > 0;
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
