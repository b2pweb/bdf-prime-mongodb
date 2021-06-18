<?php

namespace Bdf\Prime\MongoDB\Driver\ResultSet;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use MongoDB\BSON\Unserializable;
use MongoDB\Driver\Cursor;

/**
 * Adapt mongo result cursor to ResultSetInterface
 */
final class CursorResultSet extends \IteratorIterator implements ResultSetInterface
{
    public const FETCH_RAW_ARRAY = 'raw_array';

    /**
     * @var Cursor
     */
    private $cursor;

    /**
     * @var string
     */
    private $fetchMode;

    /**
     * @var mixed
     */
    private $fetchOptions;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var \ReflectionProperty[]
     */
    private $reflectionProperties;


    /**
     * CursorResultSet constructor.
     *
     * @param Cursor $cursor
     */
    public function __construct(Cursor $cursor)
    {
        parent::__construct($cursor);

        $this->cursor = $cursor;
        $this->fetchMode(self::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
        switch ($mode) {
            case self::FETCH_OBJECT:
                $this->cursor->setTypeMap([]);
                break;

            case self::FETCH_CLASS:
                if (is_subclass_of($options, Unserializable::class)) {
                    $this->cursor->setTypeMap(['root' => $options]);
                    break;
                }
                // No break

            case self::FETCH_RAW_ARRAY:
            case self::FETCH_ASSOC:
            case self::FETCH_NUM:
            case self::FETCH_COLUMN:
                $this->cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
                break;
        }

        $this->fetchMode = $mode;
        $this->fetchOptions = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return iterator_to_array($this, false);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->all());
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $value = parent::current();

        switch ($this->fetchMode) {
            case self::FETCH_ASSOC:
                return $this->toFlatArray($value);

            case self::FETCH_NUM:
                return $this->toIndexedArray($value);

            case self::FETCH_COLUMN:
                return $this->extractColumn($value, $this->fetchOptions);

            case self::FETCH_CLASS:
                if (is_array($value)) {
                    return $this->toClass($value);
                }
                // No break

            default:
                return $value;
        }
    }

    /**
     * Convert multi-dimensional array to flat array
     *
     * @param array $data
     * @param string $base
     *
     * @return array
     */
    private function toFlatArray(array $data, $base = '')
    {
        $flatten = [];

        foreach ($data as $k => $v) {
            $key = empty($base) ? $k : $base . '.' . $k;

            if (is_array($v) && is_string(key($v))) {
                $flatten = array_merge($flatten, $this->toFlatArray($v, $key));
            } else {
                $flatten[$key] = $v;
            }
        }

        return $flatten;
    }

    /**
     * Convert multi-dimensional array to numeric indexed array
     *
     * @param array $data
     *
     * @return array
     */
    private function toIndexedArray(array $data)
    {
        $indexed = [];

        foreach ($data as $value) {
            if (is_array($value)) {
                $indexed = array_merge($indexed, $this->toIndexedArray($value));
            } else {
                $indexed[] = $value;
            }
        }

        return $indexed;
    }

    /**
     * Extract one column value by its index
     *
     * @param array $data
     * @param integer $column
     * @param int $index in-out : The current index value
     *
     * @return mixed
     */
    private function extractColumn(array $data, $column, &$index = 0)
    {
        if ($column === $index) {
            return reset($data);
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $value = $this->extractColumn($value, $column, $index);
            }

            if ($index === $column) {
                return $value;
            }

            ++$index;
        }

        // Remove the last increment
        --$index;

        return null;
    }

    /**
     * Convert data to class
     *
     * @param array $data
     *
     * @return object
     * @throws \ReflectionException
     */
    private function toClass(array $data)
    {
        if (!$this->reflectionClass) {
            $this->reflectionClass = new \ReflectionClass($this->fetchOptions);
        }

        $object = $this->reflectionClass->newInstance();

        foreach ($data as $property => $value) {
            if (!isset($this->reflectionProperties[$property])) {
                $this->reflectionProperties[$property] = $this->reflectionClass->getProperty($property);
                $this->reflectionProperties[$property]->setAccessible(true);
            }

            $this->reflectionProperties[$property]->setValue($object, $value);
        }

        return $object;
    }
}
