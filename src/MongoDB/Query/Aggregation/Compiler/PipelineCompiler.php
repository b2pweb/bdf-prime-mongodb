<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Compiler;

use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Command\Aggregate;
use Bdf\Prime\MongoDB\Query\MongoCompiler;
use Bdf\Prime\Query\Clause;

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
    public function compileAggregate(Clause $clause)
    {
        $command = new Aggregate($clause->statements['collection']);

        if ($clause->statements['columns']) {
            $command->add(['$project' => $this->compileProject($clause->statements['columns'])]);
        }

        if ($clause->statements['where']) {
            $command->add(['$match' => $this->compileMatch($clause->statements['where'])]);
        }

        foreach ($this->compilePipeline($clause->statements['pipeline']) as $stage) {
            $command->add($stage);
        }

        if ($clause->statements['orders']) {
            $command->add(['$sort' => $this->compileSort($clause->statements['orders'])]);
        }

        if ($clause->statements['limit']) {
            $command->add(['$limit' => $this->compileLimit($clause->statements['limit'])]);
        }

        if ($clause->statements['offset']) {
            $command->add(['$skip' => $this->compileSkip($clause->statements['offset'])]);
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function compilePipeline(array $stages)
    {
        $pipeline = [];

        foreach ($stages as $stage) {
            $pipeline[] = [$stage->operator() => $stage->compile($this)];
        }

        return $pipeline;
    }

    /**
     * {@inheritdoc}
     */
    public function compileGroup(array $group)
    {
        $compiled = [
            '_id' => $this->compileGroupId($group['_id'])
        ];

        unset($group['_id']);

        // @todo Analyze accumulator operators
        foreach ($group as $field => $expression) {
            $compiled[$field] = $this->compiler->compileExpression($expression);
        }

        return $compiled;
    }

    /**
     * {@inheritdoc}
     */
    public function compileMatch(array $match)
    {
        return $this->compiler->compileFilters($match);
    }

    /**
     * {@inheritdoc}
     */
    public function compileProject(array $project)
    {
        return $this->compiler->compileProjection($project);
    }

    /**
     * {@inheritdoc}
     */
    public function compileSort(array $sort)
    {
        return $this->compiler->compileSort($sort);
    }

    /**
     * {@inheritdoc}
     */
    public function compileLimit($limit)
    {
        return $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function compileSkip($skip)
    {
        return $skip;
    }

    /**
     * @todo Use CompilerInterface ?
     */
    public function reset()
    {
        
    }

    /**
     * Compile the $group _id expression
     *
     * @param mixed $expression
     *
     * @return mixed
     */
    protected function compileGroupId($expression)
    {
        if ($expression === null) {
            return null;
        }

        if (is_string($expression)) {
            return '$'.$this->compiler->preprocessor()->field($expression);
        }

        $compiled = [];

        foreach ($this->compiler->compileProjection($expression) as $field => $subExpression) {
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
