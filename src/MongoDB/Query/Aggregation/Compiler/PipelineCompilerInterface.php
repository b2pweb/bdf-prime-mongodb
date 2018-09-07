<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Compiler;

use Bdf\Prime\MongoDB\Query\Aggregation\Stage\StageInterface;
use Bdf\Prime\MongoDB\Query\Command\Aggregate;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compiler for pipeline stages
 *
 * @see \Bdf\Prime\MongoDB\Query\Aggregation\Pipeline
 * @see \Bdf\Prime\MongoDB\Query\Aggregation\Stage\StageInterface
 */
interface PipelineCompilerInterface
{
    /**
     * Compile the aggregation command
     *
     * @param CompilableClause $clause
     *
     * @return Aggregate
     */
    public function compileAggregate(CompilableClause $clause);

    /**
     * Compile complete pipeline stages
     *
     * @param CompilableClause $clause
     * @param StageInterface[] $stages
     *
     * @return array
     */
    public function compilePipeline(CompilableClause $clause, array $stages);

    /**
     * Compile the $group stage
     *
     * @param CompilableClause $clause
     * @param array $group
     *
     * @return array
     */
    public function compileGroup(CompilableClause $clause, array $group);

    /**
     * Compile the $match stage
     *
     * @param CompilableClause $clause
     * @param array $match
     *
     * @return array
     */
    public function compileMatch(CompilableClause $clause, array $match);

    /**
     * Compile the $project stage
     *
     * @param CompilableClause $clause
     * @param array $project
     *
     * @return array
     */
    public function compileProject(CompilableClause $clause, array $project);

    /**
     * Compile the $sort stage
     *
     * @param CompilableClause $clause
     * @param array $sort
     *
     * @return array
     */
    public function compileSort(CompilableClause $clause, array $sort);

    /**
     * Compile $limit stage
     *
     * @param CompilableClause $clause
     * @param integer $limit
     *
     * @return integer
     */
    public function compileLimit(CompilableClause $clause, $limit);

    /**
     * Compile $skip stage
     *
     * @param CompilableClause $clause
     * @param integer $skip
     *
     * @return integer
     */
    public function compileSkip(CompilableClause $clause, $skip);
}
