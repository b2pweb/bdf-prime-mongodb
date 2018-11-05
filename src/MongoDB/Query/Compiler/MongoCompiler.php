<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\AbstractCompiler;

/**
 * MongoCompiler
 */
class MongoCompiler extends AbstractCompiler
{
    /**
     * @var MongoGrammar
     */
    private $grammar;


    /**
     * MongoCompiler constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        // @todo Save grammar on platform
        $this->grammar = new MongoGrammar($this->platform);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(CompilableClause $query)
    {
        return [];
    }

    /**
     * Compile a count command
     *
     * @param CompilableClause $query
     *
     * @return Count
     *
     * @link https://docs.mongodb.com/manual/reference/command/count/
     */
    public function compileCount(CompilableClause $query)
    {
        $command = new Count($query->statements['collection']);

        if (!empty($query->statements['where'])) {
            $command->query($this->grammar->filters($query, $query->statements['where']));
        }

        if ($query->statements['limit']) {
            $command->limit($query->statements['limit']);
        }

        if ($query->statements['offset']) {
            $command->skip($query->statements['offset']);
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     *
     * @return WriteQuery
     */
    protected function doCompileInsert(CompilableClause $query)
    {
        $bulk = new WriteQuery($query->statements['collection']);

        $bulk->insert(
            $this->compileInsertData($query, $query->statements['values']['data'], $query->statements['values']['types'] ?? [])
        );

        return $bulk;
    }

    /**
     * {@inheritdoc}
     *
     * @return WriteQuery
     */
    protected function doCompileUpdate(CompilableClause $query)
    {
        $bulk = new WriteQuery($query->statements['collection']);

        if ($query->statements['replace']) {
            $data = $this->compileInsertData($query, $query->statements['values']['data'], $query->statements['values']['types'] ?? []);

            // _id is not given : perform a simple insert
            if (!isset($data['_id'])) {
                $bulk->insert($data);

                return $bulk;
            }

            // filter on id
            $filter = ['_id' => $data['_id']];
            unset($data['_id']);

            $bulk->update(
                $filter,
                [
                    '$set'         => $data,
                    '$setOnInsert' => $filter
                ],
                [
                    'upsert' => true,
                    'multi'  => false
                ]
            );
        } else {
            $bulk->update(
                $this->grammar->filters($query, $query->statements['where']),
                $this->compileUpdateOperators($query, $query->statements),
                [
                    'multi' => true
                ]
            );
        }

        return $bulk;
    }

    /**
     * {@inheritdoc}
     *
     * @return WriteQuery
     */
    protected function doCompileDelete(CompilableClause $query)
    {
        $bulk = new WriteQuery($query->statements['collection']);

        $bulk->delete(
            $this->grammar->filters($query, $query->statements['where'])
        );

        return $bulk;
    }

    /**
     * {@inheritdoc}
     *
     * @return ReadQuery
     */
    protected function doCompileSelect(CompilableClause $query)
    {
        $options = [];

        if ($query->statements['columns']) {
            $options['projection'] = $this->grammar->projection($query, $query->statements['columns']);
        }

        if ($query->statements['limit']) {
            $options['limit'] = $query->statements['limit'];
        }

        if ($query->statements['offset']) {
            $options['skip'] = $query->statements['offset'];
        }

        if ($query->statements['orders']) {
            $options['sort'] = $this->grammar->sort($query, $query->statements['orders']);
        }

        $filters = $this->grammar->filters($query, $query->statements['where']);

        return new ReadQuery($query->statements['collection'], $filters, $options);
    }

    /**
     * @param CompilableClause $query
     * @param mixed $expression
     *
     * @return mixed
     */
    public function compileExpression(CompilableClause $query, $expression)
    {
        @trigger_error(E_USER_DEPRECATED);

        return $this->grammar->expression($query, $expression);
    }

    /**
     * Compile document data for insert operation.
     * Unlike Update, the insert data should not be flatten
     *
     * @param CompilableClause $query
     * @param array $data
     * @param array $types
     *
     * @return array
     */
    protected function compileInsertData(CompilableClause $query, array $data, array $types)
    {
        $parsed = [];

        foreach ($data as $column => $value) {
            $type = $types[$column] ?? true;

            $field = explode('.', $query->preprocessor()->field($column, $type));
            $count = count($field);
            $base = &$parsed;

            for ($i = 0; $i < $count - 1; ++$i) {
                if (!isset($base[$field[$i]])) {
                    $base[$field[$i]] = [];
                }

                $base = &$base[$field[$i]];
            }

            $value = $this->platform->types()->toDatabase($value, $type);
            $base[$field[$i]] = $value;
        }

        return $parsed;
    }

    /**
     * @param CompilableClause $query
     * @param array $filters
     *
     * @return array
     */
    public function compileFilters(CompilableClause $query, array $filters)
    {
        @trigger_error(E_USER_DEPRECATED);

        return $this->grammar->filters($query, $filters);
    }

    /**
     * @param CompilableClause $query
     * @param array $columns
     *
     * @return array
     */
    public function compileProjection(CompilableClause $query, array $columns)
    {
        @trigger_error(E_USER_DEPRECATED);

        return $this->grammar->projection($query, $columns);
    }

    /**
     * @param CompilableClause $query
     * @param array $statements
     *
     * @return array
     */
    public function compileUpdateOperators(CompilableClause $query, array $statements)
    {
        $operators = $statements['update'];

        if (!empty($statements['values'])) {
            $operators += $this->grammar->set($query, $statements['values']['data'], $statements['values']['types']);
        }

        return $operators;
    }

    /**
     * @param CompilableClause $query
     * @param array $orders
     *
     * @return array
     */
    public function compileSort(CompilableClause $query, array $orders)
    {
        @trigger_error(E_USER_DEPRECATED);

        return $this->grammar->sort($query, $orders);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier(CompilableClause $query, $column)
    {
        return $column;
    }
}
