<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\StageInterface;
use Bdf\Prime\MongoDB\Query\Command\Aggregate;
use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compiler class for @see Pipeline
 */
class PipelineCompiler
{
    private MongoGrammar $grammar;


    /**
     * PipelineCompiler constructor.
     *
     * @param MongoConnection $connection
     */
    public function __construct(MongoConnection $connection)
    {
        $this->grammar = new MongoGrammar($connection->platform());
    }

    /**
     * Compile pipeline to Aggregate command
     *
     * @param CompilableClause $clause
     *
     * @return Aggregate
     */
    public function compileAggregate(CompilableClause $clause): Aggregate
    {
        $command = new Aggregate($clause->statements['collection']);

        if ($clause->statements['columns']) {
            $command->add(['$project' => $this->grammar->projection($clause, $clause->statements['columns'])]);
        }

        if ($clause->statements['where']) {
            $command->add(['$match' => $this->grammar->filters($clause, $clause->statements['where'])]);
        }

        foreach ($this->compilePipeline($clause, $clause->statements['pipeline']) as $stage) {
            $command->add($stage);
        }

        if ($clause->statements['orders']) {
            $command->add(['$sort' => $this->grammar->sort($clause, $clause->statements['orders'])]);
        }

        if ($clause->statements['limit']) {
            $command->add(['$limit' => $clause->statements['limit']]);
        }

        if ($clause->statements['offset']) {
            $command->add(['$skip' => $clause->statements['offset']]);
        }

        $command->cursor([]);

        return $command;
    }

    /**
     * Compile the stage pipeline
     *
     * @param CompilableClause $clause
     * @param StageInterface[] $stages
     *
     * @return array
     */
    public function compilePipeline(CompilableClause $clause, array $stages): array
    {
        $pipeline = [];

        foreach ($stages as $stage) {
            $pipeline[] = [$stage->operator() => $stage->compile($clause, $this->grammar)];
        }

        return $pipeline;
    }
}
