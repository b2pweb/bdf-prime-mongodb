<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Compiler;

use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Command\Aggregate;
use Bdf\Prime\MongoDB\Query\Compiler\MongoCompiler;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compiler class for @see Pipeline
 */
class PipelineCompiler implements PipelineCompilerInterface
{
    /**
     * @var MongoCompiler
     */
    private $compiler;


    /**
     * PipelineCompiler constructor.
     *
     * @param MongoCompiler $compiler
     */
    public function __construct(MongoCompiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * {@inheritdoc}
     */
    public function compileAggregate(CompilableClause $clause)
    {
        $command = new Aggregate($clause->statements['collection']);

        if ($clause->statements['columns']) {
            $command->add(['$project' => $this->compileProject($clause, $clause->statements['columns'])]);
        }

        if ($clause->statements['where']) {
            $command->add(['$match' => $this->compileMatch($clause, $clause->statements['where'])]);
        }

        foreach ($this->compilePipeline($clause, $clause->statements['pipeline']) as $stage) {
            $command->add($stage);
        }

        if ($clause->statements['orders']) {
            $command->add(['$sort' => $this->compileSort($clause, $clause->statements['orders'])]);
        }

        if ($clause->statements['limit']) {
            $command->add(['$limit' => $this->compileLimit($clause, $clause->statements['limit'])]);
        }

        if ($clause->statements['offset']) {
            $command->add(['$skip' => $this->compileSkip($clause, $clause->statements['offset'])]);
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function compilePipeline(CompilableClause $clause, array $stages)
    {
        $pipeline = [];

        foreach ($stages as $stage) {
            $pipeline[] = [$stage->operator() => $stage->compile($clause, $this)];
        }

        return $pipeline;
    }

    /**
     * {@inheritdoc}
     */
    public function compileGroup(CompilableClause $clause, array $group)
    {
        $compiled = [
            '_id' => $this->compileGroupId($clause, $group['_id'])
        ];

        unset($group['_id']);

        // @todo Analyze accumulator operators
        foreach ($group as $field => $expression) {
            $compiled[$field] = $this->compiler->compileExpression($clause, $expression);
        }

        return $compiled;
    }

    /**
     * {@inheritdoc}
     */
    public function compileMatch(CompilableClause $clause, array $match)
    {
        return $this->compiler->compileFilters($clause, $match);
    }

    /**
     * {@inheritdoc}
     */
    public function compileProject(CompilableClause $clause, array $project)
    {
        return $this->compiler->compileProjection($clause, $project);
    }

    /**
     * {@inheritdoc}
     */
    public function compileSort(CompilableClause $clause, array $sort)
    {
        return $this->compiler->compileSort($clause, $sort);
    }

    /**
     * {@inheritdoc}
     */
    public function compileLimit(CompilableClause $clause, $limit)
    {
        return $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function compileSkip(CompilableClause $clause, $skip)
    {
        return $skip;
    }

    /**
     * Compile the $group _id expression
     *
     * @param CompilableClause $clause
     * @param mixed $expression
     *
     * @return mixed
     */
    protected function compileGroupId(CompilableClause $clause, $expression)
    {
        if ($expression === null) {
            return null;
        }

        if (is_string($expression)) {
            return '$'.$clause->preprocessor()->field($expression);
        }

        $compiled = [];

        foreach ($this->compiler->compileProjection($clause, $expression) as $field => $subExpression) {
            if ($subExpression === false) {
                continue;
            }

            if (($subExpression === true || $subExpression === 1) && $field[0] !== '$') {
                $compiled[$field] = '$'.$field;
                continue;
            }

            $compiled[$field] = $subExpression;
        }

        return $compiled;
    }
}
