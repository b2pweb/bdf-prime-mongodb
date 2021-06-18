<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use Bdf\Prime\Query\CompilableClause;

/**
 * Limits the number of documents passed to the next stage in the pipeline.
 *
 * https://docs.mongodb.com/manual/reference/operator/aggregation/limit/
 */
class Limit implements StageInterface
{
    /**
     * @var integer
     */
    private $limit;


    /**
     * Limit constructor.
     *
     * @param int $limit
     */
    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function operator()
    {
        return '$limit';
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(CompilableClause $clause, MongoGrammar $grammar)
    {
        return $this->limit;
    }
}
