<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Group;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Limit;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Match;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Project;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Skip;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\Sort;
use Bdf\Prime\MongoDB\Query\Aggregation\Stage\StageInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\Extension\SimpleWhereTrait;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * MongoDB Aggregation pipeline
 *
 * @link https://docs.mongodb.com/manual/core/aggregation-pipeline/
 *
 * @todo use pagination
 */
class Pipeline extends CompilableClause implements PipelineInterface, Whereable, CommandInterface, Compilable
{
    use SimpleWhereTrait;

    /**
     * @var MongoConnection
     */
    private $connection;

    /**
     * @var PipelineCompiler
     */
    private $compiler;


    /**
     * Pipeline constructor.
     *
     * @param ConnectionInterface $connection
     * @param PreprocessorInterface|null $preprocessor
     * @param CompilerState|null $state
     */
    public function __construct(ConnectionInterface $connection, PreprocessorInterface $preprocessor = null, CompilerState $state = null)
    {
        parent::__construct($preprocessor ?: new DefaultPreprocessor(), $state ?: new CompilerState());

        $this->on($connection);
        $this->statements = [
            'collection' => null,
            'columns'    => [],
            'where'      => [],
            'orders'     => null,
            'limit'      => null,
            'offset'     => null,
            'pipeline'   => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function compiler()
    {
        return $this->compiler;
    }

    /**
     * {@inheritdoc}
     */
    public function setCompiler(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function on(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->compiler   = $connection->factory()->compiler(static::class);
    }

    /**
     * {@inheritdoc}
     */
    public function from($from, $alias = null)
    {
        $this->statements['collection'] = $from;

        return $this;
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
    public function sort($fields, $order = null)
    {
        $this->statements['pipeline'][] = Sort::make($fields, $order ?: 'asc');

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
     * {@inheritdoc}
     */
    public function execute($columns = null)
    {
        if ($columns !== null) {
            $this->project($columns);
        }

        return $this->connection->execute($this)->fetchMode(CursorResultSet::FETCH_RAW_ARRAY)->all();
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
                    'nested' => array_map(function (Match $match) {
                        return $match->export()[0];
                    }, $this->statements['pipeline']),
                    'glue'   => $type,
                ]
            ]
        ]);

        $this->statements = $statements;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile($forceRecompile = false)
    {
        return $this->compiler->compileAggregate($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        return self::TYPE_SELECT;
    }
}
