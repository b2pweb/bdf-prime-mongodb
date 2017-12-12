<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\MongoDB\Query\Aggregation\Compiler\PipelineCompilerInterface;

/**
 * Match operator
 *
 * @link https://docs.mongodb.com/manual/reference/operator/aggregation/match/#pipe._S_match
 */
class Match implements StageInterface
{
    /**
     * @var array
     */
    private $statements;


    /**
     * Match constructor.
     *
     * @param array $statements
     */
    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

    /**
     * {@inheritdoc}
     */
    public function operator()
    {
        return '$match';
    }

    /**
     * {@inheritdoc}
     */
    public function compile(PipelineCompilerInterface $compiler)
    {
        return $compiler->compileMatch($this->export());
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this->statements['where'];
    }
}
