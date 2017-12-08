<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\Query\AbstractQuery;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Extension\LimitableTrait;
use Bdf\Prime\Query\Extension\OrderableTrait;
use Bdf\Prime\Query\Extension\PaginableTrait;
use Bdf\Prime\Query\QueryInterface;
use MongoDB\Driver\Cursor;

/**
 * MongoQuery
 *
 * @property MongoConnection $connection
 * @property MongoCompiler $compiler
 */
class MongoQuery extends AbstractQuery implements QueryInterface, Orderable, Paginable/*, Aggregatable*/ // @todo uncomment on #14561
{
    use PaginableTrait;
    use LimitableTrait;
    use OrderableTrait;


    /**
     * MongoQuery constructor.
     *
     * @param MongoConnection $connection
     * @param CompilerInterface $compiler
     */
    public function __construct(MongoConnection $connection, CompilerInterface $compiler = null)
    {
        parent::__construct($connection, $compiler ?: new MongoCompiler());

        $this->statements = [
            'collection' => null,
            'columns'    => [],
            'where'      => [],
            'values'     => [],
            'update'     => [],
            'limit'      => null,
            'offset'     => null,
            'orders'     => [],
            'replace'    => false,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return Cursor
     */
    public function execute($columns = null)
    {
        if ($columns !== null) {
            $this->select($columns);
        }

        return $this->connection->executeSelect(
            $this->statements['collection'],
            $this->compiler->compileSelect($this)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function from($from, $alias = null)
    {
        $this->statements['collection'] = $from;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->connection->executeWrite(
            $this->statements['collection'],
            $this->compiler->compileDelete($this)
        )->getDeletedCount();
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $data = [], array $types = [])
    {
        if ($data) {
            $this->statements['values'] = [
                'data' => $data,
                'types' => $types
            ];
        }

        return $this->connection->executeWrite(
            $this->statements['collection'],
            $this->compiler->compileUpdate($this)
        )->getModifiedCount();
    }

    /**
     * {@inheritdoc}
     */
    public function insert(array $data = [])
    {
        if ($data) {
            $this->statements['values'] = [
                'data' => $data,
            ];
        }

        return $this->connection->executeWrite(
            $this->statements['collection'],
            $this->compiler->compileInsert($this)
        )->getInsertedCount();
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $values = [])
    {
        $this->statements['replace'] = true;

        if ($values) {
            $this->statements['values'] = [
                'data' => $values
            ];
        }

        return $this->connection->executeWrite(
            $this->statements['collection'],
            $this->compiler->compileUpdate($this)
        )->getUpsertedCount();
    }

    /**
     * Increment a field on an update operation
     * @link https://docs.mongodb.com/manual/reference/operator/update/inc/
     *
     * @param string $attribute
     * @param int $amount
     *
     * @return $this
     */
    public function inc($attribute, $amount = 1)
    {
        $this->statements['update']['$inc'][$attribute] = $amount;

        return $this;
    }

    /**
     * Multiply a field on an update operation
     * @link https://docs.mongodb.com/manual/reference/operator/update/mul/
     *
     * @param string $attribute
     * @param double|int $number
     *
     * @return $this
     */
    public function mul($attribute, $number)
    {
        $this->statements['update']['$mul'][$attribute] = $number;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param Cursor $data
     */
    public function postProcessResult($data)
    {
        return parent::postProcessResult(
            array_map([$this, 'flattenArray'], $data->toArray())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function inRow($column)
    {
        // @todo flatten array ?
        $result = $this->limit(1)->execute($column)->toArray();

        return isset($result[0]) ? reset($result[0]) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function paginationCount($columns = null)
    {
        return $this->count($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function count($column = null)
    {
        return $this->connection->runCommand(
            $this->compiler->compileCount($this)
        )->toArray()[0]->n;
    }

    /**
     * Convert multi-dimensional array to flat array
     *
     * @param array $data
     * @param string $base
     *
     * @return array
     */
    protected function flattenArray(array $data, $base = '')
    {
        $flatten = [];

        foreach ($data as $k => $v) {
            $key = empty($base) ? $k : $base.'.'.$k;

            if (is_array($v) && is_string(key($v))) {
                $flatten = array_merge($flatten, $this->flattenArray($v, $key));
            } else {
                $flatten[$key] = $v;
            }
        }

        return $flatten;
    }
}
