<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\DBALException;
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
 */
final class MongoKeyValueQuery extends AbstractReadCommand implements KeyValueQueryInterface, Compilable, Paginable, Limitable
{
    const TYPE_COUNT = 'count';

    use CompilableTrait;
    use LimitableTrait;
    use PaginableTrait;
    use ProjectionableTrait;

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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function from($table, $alias = null)
    {
        if ($this->statements['collection'] !== $table) {
            $this->compilerState->invalidate();
            $this->statements['collection'] = $table;
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
    public function count($column = null)
    {
        $this->setType(self::TYPE_COUNT);

        foreach ($this->connection->execute($this)->fetchMode(ResultSetInterface::FETCH_OBJECT) as $count) {
            return $count->n;
        }

        throw new DBALException('Invalid result for count command');
    }

    /**
     * {@inheritdoc}
     */
    public function avg($column = null)
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function min($column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function max($column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function sum($column = null)
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function aggregate($function, $column = null)
    {
        throw new \BadMethodCallException(__METHOD__.' not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null)
    {
        $this->setType(self::TYPE_SELECT);

        if (!empty($columns)) {
            $this->select($columns);
        }

        try {
            return $this->connection->execute($this)->all();
        } catch (DBALException $e) {
            //Encapsulation des exceptions de la couche basse.
            //Permet d'eviter la remonté des infos systèmes en cas de catch non intentionnel
            throw new DBALException('dbal internal error has occurred', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
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
    public function update($values = null)
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
     * {@inheritdoc}
     */
    public function getBindings()
    {
        return [];
    }
}
