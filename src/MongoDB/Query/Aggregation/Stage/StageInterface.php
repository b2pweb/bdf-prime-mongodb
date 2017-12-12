<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;
use Bdf\Prime\MongoDB\Query\Aggregation\Compiler\PipelineCompilerInterface;

/**
 *
 */
interface StageInterface
{
    /**
     * Get the stage operator name
     *
     * @return string
     */
    public function operator();

    /**
     * Get the stage operations in normalized form
     *
     * @return array
     */
    public function export();

    /**
     * Compile the current stage
     *
     * @param PipelineCompilerInterface $compiler
     *
     * @return array
     */
    public function compile(PipelineCompilerInterface $compiler);
}
