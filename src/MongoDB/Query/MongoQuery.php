<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Compiler\MongoCompiler;
use Bdf\Prime\Query\AbstractQuery;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Extension\LimitableTrait;
use Bdf\Prime\Query\Extension\OrderableTrait;
use Bdf\Prime\Query\Extension\PaginableTrait;
use Bdf\Prime\Query\QueryInterface;

/**
 * MongoQuery
 *
 * @property MongoConnection $connection
 * @property MongoCompiler $compiler
 */
class MongoQuery extends AbstractQuery implements QueryInterface, Orderable, Paginable, Aggregatable, Limitable, OptionsConfigurable
{
    use PaginableTrait;
    use LimitableTrait;
    use OptionsTrait;
    use OrderableTrait;


    /**
     * MongoQuery constructor.
     *
     * @param MongoConnection $connection
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(MongoConnection $connection, PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($connection, $preprocessor ?: new DefaultPreprocessor());

        $this->statements = [
            'collection' => null,
            'columns'    => [],
            'where'      => [],
            'values'     => [],
            'update'     => [],
            'limit'      => null,
            'offset'     => null,
            'orders'     => [],
            'replace'    => false,
            'options'    => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null)
    {
        if ($columns !== null) {
            $this->select($columns);
        }

        $this->setType(self::TYPE_SELECT);

        return $this->connection
            ->execute($this)
            ->fetchMode(ResultSetInterface::FETCH_ASSOC)
            ->all()
        ;
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
    public function delete(): int
    {
        $this->setType(self::TYPE_DELETE);

        return $this->connection->execute($this)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $data = [], array $types = []): int
    {
        if ($data) {
            $this->statements['values'] = [
                'data' => $data,
                'types' => $types
            ];
        }

        $this->setType(self::TYPE_UPDATE);

        return $this->connection->execute($this)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function insert(array $data = []): int
    {
        if ($data) {
            $this->statements['values'] = [
                'data' => $data,
            ];
        }

        $this->setType(self::TYPE_INSERT);

        return $this->connection->execute($this)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $values = []): int
    {
        $this->statements['replace'] = true;

        if ($values) {
            $this->statements['values'] = [
                'data' => $values
            ];
        }

        $this->setType(self::TYPE_UPDATE);

        return $this->connection->execute($this)->count();
    }

    /**
     * Increment a field on an update operation
     * @link https://docs.mongodb.com/manual/reference/operator/update/inc/
     *
     * @param string $attribute
     * @param int $amount
     *
     * @return $this
     */
    public function inc($attribute, $amount = 1)
    {
        $this->statements['update']['$inc'][$attribute] = $amount;

        return $this;
    }

    /**
     * Multiply a field on an update operation
     * @link https://docs.mongodb.com/manual/reference/operator/update/mul/
     *
     * @param string $attribute
     * @param double|int $number
     *
     * @return $this
     */
    public function mul($attribute, $number)
    {
        $this->statements['update']['$mul'][$attribute] = $number;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function paginationCount(?string $column = null): int
    {
        $statements = $this->statements;

        try {
            $this->statements['limit']  = null;
            $this->statements['offset'] = null;

            return $this->count($column);
        } finally {
            $this->statements = $statements;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(?string $column = null): int
    {
        return $this->connection->runCommand(
            $this->compiler->compileCount($this)
        )->toArray()[0]->n;
    }

    /**
     * {@inheritdoc}
     */
    public function avg(?string $column = null): float
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function min(?string $column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function max(?string $column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function sum(?string $column = null): float
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function aggregate(string $function, ?string $column = null)
    {
        return $this
            ->group(null, ['aggregate' => [$function => $column]])
            ->execute()[0]['aggregate']
        ;
    }

    /**
     * Create a new Aggregation pipeline query
     *
     * @return Pipeline The new query instance
     *
     * @link https://docs.mongodb.com/manual/core/aggregation-pipeline/
     */
    public function pipeline(): Pipeline
    {
        $pipeline = new Pipeline($this->connection(), $this->preprocessor(), $this->state());

        /** @psalm-suppress InvalidArgument */
        $pipeline->setCustomFilters($this->customFilters);
        $pipeline->statements = $this->statements + $pipeline->statements;

        return $pipeline;
    }

    /**
     * Perform a group aggregation
     *
     * <code>
     * // Select users with name starting with a, grouping by their customer id
     * $query
     *     ->from('users')
     *     ->where('name', 'like', 'a%')
     *     ->group('customer.id')
     *     ->execute()
     * ;
     * </code>
     *
     * @param mixed $expression
     * @param mixed $operations
     *
     * @return Pipeline
     *
     * @see Pipeline::group()
     */
    public function group($expression = null, $operations = null): Pipeline
    {
        return $this
            ->pipeline()
            ->group($expression, $operations)
        ;
    }
}
