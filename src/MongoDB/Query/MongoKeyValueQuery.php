<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Compiler\MongoKeyValueCompiler;
use Bdf\Prime\Query\AbstractReadCommand;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Extension\CompilableTrait;
use Bdf\Prime\Query\Extension\LimitableTrait;
use Bdf\Prime\Query\Extension\PaginableTrait;
use Bdf\Prime\Query\Extension\ProjectionableTrait;

/**
 * KeyValue query implementation for MongoDB
 *
 * @template R as object|array
 *
 * @extends AbstractReadCommand<MongoConnection, R>
 * @implements KeyValueQueryInterface<MongoConnection, R>
 * @implements Paginable<R>
 */
final class MongoKeyValueQuery extends AbstractReadCommand implements KeyValueQueryInterface, Compilable, Paginable, Limitable, OptionsConfigurable
{
    use CompilableTrait {
        doCompilation as baseCompilation;
    }
    use LimitableTrait;
    use OptionsTrait;
    /** @use PaginableTrait<R> */
    use PaginableTrait;
    use ProjectionableTrait;

    public const TYPE_COUNT = 'count';

    /**
     * MongoKeyValueQuery constructor.
     *
     * @param ConnectionInterface $connection
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(ConnectionInterface $connection, PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($connection, $preprocessor ?: new DefaultPreprocessor());

        $this->statements = [
            'where'      => [],
            'collection' => null,
            'columns'    => [],
            'aggregate'  => null,
            'limit'      => null,
            'offset'     => null,
            'values'     => [
                'data'  => [],
                'types' => [],
            ],
            'options' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function from($from, $alias = null)
    {
        if ($this->statements['collection'] !== $from) {
            $this->compilerState->invalidate();
            $this->statements['collection'] = $from;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($field, $value = null)
    {
        $this->compilerState->invalidate();

        if (is_array($field)) {
            $this->statements['where'] = $field + $this->statements['where'];
        } else {
            $this->statements['where'][$field] = $value;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $values = [], array $types = [])
    {
        $this->compilerState->invalidate();

        $this->statements['values'] = [
            'data'  => $values,
            'types' => $types,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count(?string $column = null): int
    {
        /** @psalm-suppress InvalidArgument */
        $this->setType(self::TYPE_COUNT);

        foreach ($this->connection->execute($this)->asObject() as $count) {
            return $count->n;
        }

        throw new DBALException('Invalid result for count command');
    }

    /**
     * {@inheritdoc}
     */
    public function paginationCount(?string $column = null): int
    {
        // Backup statements
        $statements = $this->statements;

        // Remove pagination parameters
        $this->statements['limit'] = null;
        $this->statements['offset'] = null;

        try {
            return $this->count($column);
        } finally {
            $this->statements = $statements;
        }
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
        $result = $this
            ->pipeline()
            ->group(null, ['aggregate' => [$function => $column]])
            ->execute()
            ->asObject()
        ;

        foreach ($result as $row) {
            return $row->aggregate;
        }

        return null;
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

        $pipeline->statements['collection'] = $this->statements['collection'];
        $pipeline->statements['columns'] = $this->statements['columns'];
        $pipeline->statements['limit'] = $this->statements['limit'];
        $pipeline->statements['offset'] = $this->statements['offset'];

        if ($this->statements['where']) {
            $pipeline->where($this->statements['where']);
        }

        return $pipeline;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null): ResultSetInterface
    {
        $this->setType(self::TYPE_SELECT);

        if (!empty($columns)) {
            $this->select($columns);
        }

        try {
            return $this->connection->execute($this);
        } catch (DBALException $e) {
            //Encapsulation des exceptions de la couche basse.
            //Permet d'eviter la remonté des infos systèmes en cas de catch non intentionnel
            throw new DBALException('dbal internal error has occurred', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): int
    {
        $this->setType(self::TYPE_DELETE);

        try {
            return $this->connection->execute($this)->count();
        } catch (DBALException $e) {
            //Encapsulation des exceptions de la couche basse.
            //Permet d'eviter la remonté des infos systèmes en cas de catch non intentionnel
            throw new DBALException('dbal internal error has occurred', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($values = null): int
    {
        if ($values !== null) {
            $this->values($values);
        }

        $this->setType(self::TYPE_UPDATE);

        try {
            return $this->connection->execute($this)->count();
        } catch (DBALException $e) {
            //Encapsulation des exceptions de la couche basse.
            //Permet d'eviter la remonté des infos systèmes en cas de catch non intentionnel
            throw new DBALException('dbal internal error has occurred', 0, $e);
        }
    }

    /**
     * @param string $type
     * @param MongoKeyValueCompiler $compiler
     * @return mixed
     */
    protected function doCompilation(string $type, object $compiler)
    {
        if ($type === self::TYPE_COUNT) {
            return $compiler->compileCount($this);
        }

        return $this->baseCompilation($type, $compiler);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return [];
    }
}
