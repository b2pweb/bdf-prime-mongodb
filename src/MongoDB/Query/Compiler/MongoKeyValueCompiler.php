<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoKeyValueQuery;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\AbstractCompiler;

/**
 * Compiler for @see MongoKeyValueQuery
 */
class MongoKeyValueCompiler extends AbstractCompiler
{
    /**
     * {@inheritdoc}
     */
    protected function doCompileInsert(CompilableClause $query)
    {
        throw new \BadMethodCallException('INSERT operation is not supported on key value query');
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileUpdate(CompilableClause $query)
    {
        $bulk = new WriteQuery($query->statements['table']);

        $bulk->update(
            $this->compileFilters($query, $query->statements['where']),
            $this->compileUpdateOperators($query, $query->statements),
            [
                'multi' => true
            ]
        );

        return $bulk;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileDelete(CompilableClause $query)
    {
        $bulk = new WriteQuery($query->statements['table']);

        $bulk->delete($this->compileFilters($query, $query->statements['where']));

        return $bulk;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileSelect(CompilableClause $query)
    {
        $options = [];

        if ($query->statements['columns']) {
            $options['projection'] = $this->compileProjection($query, $query->statements['columns']);
        }

        if ($query->statements['limit']) {
            $options['limit'] = $query->statements['limit'];
        }

        if ($query->statements['offset']) {
            $options['skip'] = $query->statements['offset'];
        }

        return new ReadQuery(
            $query->statements['table'],
            $this->compileFilters($query, $query->statements['where']),
            $options
        );
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
        $command = new Count($query->statements['table']);

        if (!empty($query->statements['where'])) {
            $command->query($this->compileFilters($query, $query->statements['where']));
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
     * @param CompilableClause $query
     * @param array $data
     * @param array $types
     *
     * @return array
     *
     * @todo refactor
     */
    protected function compileUpdateData(CompilableClause $query, array $data, array $types)
    {
        $parsed = [];

        foreach ($data as $column => $value) {
            $type = $types[$column] ?? true;
            $field = $query->preprocessor()->field($column, $type);

            $parsed[$field] = $this->platform->types()->toDatabase($value, $type);
        }

        return $parsed;
    }

    /**
     * @param CompilableClause $query
     * @param array $statements
     *
     * @return array
     *
     * @todo refactor
     */
    public function compileUpdateOperators(CompilableClause $query, array $statements)
    {
        $operators = $statements['update'] ?? [];

        if (!empty($statements['values'])) {
            $operators['$set'] = $this->compileUpdateData($query, $statements['values']['data'], $statements['values']['types']);
        }

        return $operators;
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier(CompilableClause $query, $column)
    {
        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(CompilableClause $query)
    {
        return [];
    }

    /**
     * Compile filters and convert to db value
     *
     * @param CompilableClause $query
     * @param array $filters
     *
     * @return array
     */
    private function compileFilters(CompilableClause $query, array $filters)
    {
        $compiled = [];

        foreach ($filters as $field => $value) {
            $type = true;

            $field = $query->preprocessor()->field($field, $type);
            $value = $this->platform->types()->toDatabase($value, $type === true ? null : $type);

            $compiled[$field] = $value;
        }

        return $compiled;
    }

    /**
     * @param CompilableClause $query
     * @param array $columns
     *
     * @return array
     *
     * @todo refactor
     * @fixme do not supports alias
     */
    public function compileProjection(CompilableClause $query, array $columns)
    {
        $projection = [];

        foreach ($columns as $column) {
            if ($column['column'] === '*') {
                return [];
            }

            $field = $query->preprocessor()->field($column['column']);
            $projection[$field] = true;
        }

        //If column has been selected, but not _id => do not select _id
        if (!isset($projection['_id'])) {
            $projection['_id'] = false;
        }

        return $projection;
    }
}
