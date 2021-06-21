<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use Bdf\Prime\Query\CompilableClause;

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
     * @return scalar|array
     */
    public function export();

    /**
     * Compile the current stage
     *
     * @param CompilableClause $clause
     * @param MongoGrammar $grammar
     *
     * @return scalar|array
     */
    public function compile(CompilableClause $clause, MongoGrammar $grammar);
}
