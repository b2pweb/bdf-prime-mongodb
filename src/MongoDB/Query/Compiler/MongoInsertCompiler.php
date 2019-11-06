<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\AbstractCompiler;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;

/**
 * Compiler for @see BulkInsertQuery
 */
class MongoInsertCompiler extends AbstractCompiler
{
    /**
     * {@inheritdoc}
     */
    public function getBindings(CompilableClause $query)
    {
        return [];
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
     *
     * @return WriteQuery
     */
    protected function doCompileInsert(CompilableClause $query)
    {
        $bulk = new WriteQuery($query->statements['collection']);

        if ($query->statements['mode'] === InsertQueryInterface::MODE_IGNORE) {
            $bulk->ordered(false);
        }

        $columns = $this->resolveColumns($query, $query->statements['columns']);

        foreach ($query->statements['values'] as $data) {
            $bulk->insert($this->compileInsertData($data, $columns));
        }

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

        $columns = $this->resolveColumns($query, $query->statements['columns']);

        foreach ($query->statements['values'] as $data) {
            $data = $this->compileInsertData($data, $columns);

            if (!isset($data['_id'])) {
                $bulk->insert($data);
                continue;
            }

            $filter = ['_id' => $data['_id']];

            unset($data['_id']);

            $bulk->update(
                $filter,
                [
                    '$set' => $data,
                    '$setOnInsert' => $filter
                ],
                [
                    'upsert' => true,
                    'multi'  => false
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
        throw new \BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     *
     * @return ReadQuery
     */
    protected function doCompileSelect(CompilableClause $query)
    {
        throw new \BadMethodCallException();
    }

    /**
     * Compile document data for insert operation.
     * Unlike Update, the insert data should not be flatten
     *
     * @param array $data
     * @param array $columns
     *
     * @return array
     */
    private function compileInsertData(array $data, array $columns)
    {
        $parsed = [];

        foreach ($columns as $key => $column) {
            $field = $column['path'];
            $count = count($field);
            $base = &$parsed;

            for ($i = 0; $i < $count - 1; ++$i) {
                if (!isset($base[$field[$i]])) {
                    $base[$field[$i]] = [];
                }

                $base = &$base[$field[$i]];
            }

            $value = $this->platform()->types()->toDatabase($data[$key] ?? null, $column['type']);
            $base[$field[$i]] = $value;
        }

        return $parsed;
    }

    /**
     * Resolve INSERT columns
     *
     * @param CompilableClause $query
     * @param array $columns
     *
     * @return array
     */
    private function resolveColumns(CompilableClause $query, array $columns)
    {
        if (isset($query->state()->compiledParts['columns'])) {
            return $query->state()->compiledParts['columns'];
        }

        $resolved = [];

        foreach ($columns as $column) {
            $type = $column['type'] ?: true;
            $field = $query->preprocessor()->field($column['name'], $type);

            $resolved[$column['name']] = [
                'field' => $field,
                'path'  => explode('.', $field),
                'type'  => $type === true ? null : $type
            ];
        }

        return $query->state()->compiledParts['columns'] = $resolved;
    }
}
