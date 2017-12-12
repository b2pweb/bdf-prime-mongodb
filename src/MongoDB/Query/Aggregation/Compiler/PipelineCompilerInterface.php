<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Compiler;

use Bdf\Prime\MongoDB\Query\Aggregation\Stage\StageInterface;
use Bdf\Prime\MongoDB\Query\Command\Aggregate;
use Bdf\Prime\Query\Clause;

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
     * @param Clause $clause
     *
     * @return Aggregate
     */
    public function compileAggregate(Clause $clause);

    /**
     * Compile complete pipeline stages
     *
     * @param StageInterface[] $stages
     *
     * @return array
     */
    public function compilePipeline(array $stages);

    /**
     * Compile the $group stage
     *
     * @param array $group
     *
     * @return array
     */
    public function compileGroup(array $group);

    /**
     * Compile the $match stage
     *
     * @param array $match
     *
     * @return array
     */
    public function compileMatch(array $match);

    /**
     * Compile the $project stage
     *
     * @param array $project
     *
     * @return array
     */
    public function compileProject(array $project);

    /**
     * Compile the $sort stage
     *
     * @param array $sort
     *
     * @return array
     */
    public function compileSort(array $sort);

    /**
     * Compile $limit stage
     *
     * @param integer $limit
     *
     * @return integer
     */
    public function compileLimit($limit);

    /**
     * Compile $skip stage
     *
     * @param integer $skip
     *
     * @return integer
     */
    public function compileSkip($skip);
}
