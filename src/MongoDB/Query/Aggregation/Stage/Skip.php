<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use Bdf\Prime\Query\CompilableClause;

/**
 * Skips over the specified number of documents that pass into the stage and passes the remaining documents to the next stage in the pipeline.
 *
 * @link https://docs.mongodb.com/manual/reference/operator/aggregation/skip/
 */
class Skip implements StageInterface
{
    /**
     * @var integer
     */
    private $skip;


    /**
     * Skip constructor.
     *
     * @param int $skip
     */
    public function __construct($skip)
    {
        $this->skip = $skip;
    }

    /**
     * {@inheritdoc}
     */
    public function operator()
    {
        return '$skip';
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this->skip;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(CompilableClause $clause, MongoGrammar $grammar)
    {
        return $this->skip;
    }
}
