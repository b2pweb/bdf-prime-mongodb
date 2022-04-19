<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\MongoDB\Platform\MongoPlatform;
use Bdf\Prime\MongoDB\Query\Command\Count;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\Compiled\WriteQuery;
use Bdf\Prime\MongoDB\Query\MongoKeyValueQuery;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\DeleteCompilerInterface;
use Bdf\Prime\Query\Compiler\DeleteCompilerTrait;
use Bdf\Prime\Query\Compiler\SelectCompilerInterface;
use Bdf\Prime\Query\Compiler\SelectCompilerTrait;
use Bdf\Prime\Query\Compiler\UpdateCompilerInterface;
use Bdf\Prime\Query\Compiler\UpdateCompilerTrait;

/**
 * Compiler for @see MongoKeyValueQuery
 *
 * @implements SelectCompilerInterface<MongoKeyValueQuery>
 * @implements UpdateCompilerInterface<MongoKeyValueQuery>
 * @implements DeleteCompilerInterface<MongoKeyValueQuery>
 */
class MongoKeyValueCompiler implements SelectCompilerInterface, UpdateCompilerInterface, DeleteCompilerInterface
{
    /** @use SelectCompilerTrait<MongoKeyValueQuery> */
    use SelectCompilerTrait;
    /** @use UpdateCompilerTrait<MongoKeyValueQuery> */
    use UpdateCompilerTrait;
    /** @use DeleteCompilerTrait<MongoKeyValueQuery> */
    use DeleteCompilerTrait;

    private MongoPlatform $platform;
    private MongoGrammar $grammar;


    /**
     * MongoKeyValueCompiler constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->platform = $connection->platform();
        $this->grammar = new MongoGrammar($this->platform);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileUpdate(CompilableClause $query): WriteQuery
    {
        $bulk = new WriteQuery($query->statements['collection']);

        $bulk->update(
            $this->compileFilters($query, $query->statements['where']),
            $this->grammar->set($query, $query->statements['values']['data'], $query->statements['values']['types']),
            $query->statements['options'] + [
                'multi' => true
            ]
        );

        return $bulk;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileDelete(CompilableClause $query): WriteQuery
    {
        $bulk = new WriteQuery($query->statements['collection']);

        $bulk->delete(
            $this->compileFilters($query, $query->statements['where']),
            $query->statements['options']
        );

        return $bulk;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileSelect(CompilableClause $query): ReadQuery
    {
        $options = $query->statements['options'];

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
    public function compileCount(CompilableClause $query): Count
    {
        $command = new Count($query->statements['collection'], $query->statements['options']);

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
     * Compile filters and convert to db value
     *
     * @param CompilableClause $query
     * @param array $filters
     *
     * @return array
     */
    private function compileFilters(CompilableClause $query, array $filters): array
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
}
