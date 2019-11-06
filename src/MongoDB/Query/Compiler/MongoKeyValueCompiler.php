<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\Prime\Connection\ConnectionInterface;
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
     * @var MongoGrammar
     */
    private $grammar;


    /**
     * MongoKeyValueCompiler constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this->grammar = new MongoGrammar($connection->platform());
    }

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
        $bulk = new WriteQuery($query->statements['collection']);

        $bulk->update(
            $this->compileFilters($query, $query->statements['where']),
            $this->grammar->set($query, $query->statements['values']['data'], $query->statements['values']['types']),
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
        $bulk = new WriteQuery($query->statements['collection']);

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
            $options['projection'] = $this->grammar->projection($query, $query->statements['columns']);
        }

        if ($query->statements['limit']) {
            $options['limit'] = $query->statements['limit'];
        }

        if ($query->statements['offset']) {
            $options['skip'] = $query->statements['offset'];
        }

        return new ReadQuery(
            $query->statements['collection'],
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
        $command = new Count($query->statements['collection']);

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
            $value = $this->platform()->types()->toDatabase($value, $type === true ? null : $type);

            $compiled[$field] = $value;
        }

        return $compiled;
    }
}
