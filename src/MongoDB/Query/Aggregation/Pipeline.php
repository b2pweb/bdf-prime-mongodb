<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Aggregation\Compiler\PipelineCompiler;
use Bdf\Prime\MongoDB\Query\Aggregation\Compiler\PipelineCompilerInterface;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Group;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Limit;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Match;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Project;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Skip;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Sort;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\StageInterface;
use Bdf\Prime\Query\Clause;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\Extension\SimpleWhereTrait;
use Bdf\Prime\Query\QueryInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * MongoDB Aggregation pipeline
 *
 * @link https://docs.mongodb.com/manual/core/aggregation-pipeline/
 *
 * @todo implements QueryInterface ?
 * @todo use pagination
 */
class Pipeline extends CompilableClause implements PipelineInterface, Whereable
{
    use SimpleWhereTrait;

    /**
     * @var MongoConnection
     */
    private $connection;

    /**
     * @var PipelineCompilerInterface
     */
    private $compiler;


    /**
     * Pipeline constructor.
     *
     * @param QueryInterface $query
     */
    public function __construct(QueryInterface $query)
    {
        parent::__construct($query->preprocessor(), $query->state());

        $this->connection    = $query->connection();
        $this->compiler      = new PipelineCompiler($query->compiler());
        $this->statements    = $query->statements + ['pipeline' => []];
        $this->customFilters = $query->getCustomFilters();
    }

    /**
     * {@inheritdoc}
     */
    public function group($expression = null, $operations = null)
    {
        $this->statements['pipeline'][] = Group::make($expression, $operations);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function match($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function project($fields)
    {
        $this->statements['pipeline'][] = Project::make($fields);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sort($fields, $order = 'asc')
    {
        $this->statements['pipeline'][] = Sort::make($fields, $order);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit)
    {
        $this->statements['pipeline'][] = new Limit($limit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function skip($skip)
    {
        $this->statements['pipeline'][] = new Skip($skip);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function push(StageInterface $stage)
    {
        $this->statements['pipeline'][] = $stage;
        return $this;
    }

    /**
     * Execute the aggregation request
     *
     * @return array
     */
    public function execute()
    {
        $cursor = $this->connection->runCommand($this->compiler->compileAggregate($this));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        return $cursor->toArray()[0]['result'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildClause($statement, $expression, $operator = null, $value = null, $type = CompositeExpression::TYPE_AND)
    {
        $statements = $this->statements;
        $this->statements = [$statement => []];

        parent::buildClause($statement, $expression, $operator, $value, $type);

        $statements['pipeline'][] = new Match($this->statements);
        $this->statements = $statements;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildNested($statement, \Closure $callback, $type = CompositeExpression::TYPE_AND)
    {
        $statements = $this->statements;
        $this->statements = [$statement => []];

        $callback($this);

        $statements['pipeline'][] = new Match([
            'where' => [
                [
                    'nested' => array_map(function(Match $match) {
                        return $match->export()[0];
                    }, $this->statements['pipeline']),
                    'glue'   => $type,
                ]
            ]
        ]);

        $this->statements = $statements;

        return $this;
    }
}
