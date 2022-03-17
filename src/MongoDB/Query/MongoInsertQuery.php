<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Driver\ResultSet\WriteResultSet;
use Bdf\Prime\MongoDB\Query\Compiler\MongoInsertCompiler;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use MongoDB\Driver\Exception\BulkWriteException;

/**
 * Insert query implementation for MongoDB connection
 */
final class MongoInsertQuery extends CompilableClause implements CommandInterface, Compilable, InsertQueryInterface
{
    private ConnectionInterface $connection;
    private MongoInsertCompiler $compiler;


    /**
     * BulkInsertQuery constructor.
     *
     * @param ConnectionInterface $connection
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(ConnectionInterface $connection, PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($preprocessor ?: new DefaultPreprocessor(), new CompilerState());

        $this->on($connection);

        $this->statements = [
            'collection' => null,
            'columns'    => [],
            'values'     => [],
            'mode'       => self::MODE_INSERT,
            'bulk'       => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function compiler(): MongoInsertCompiler
    {
        return $this->compiler;
    }

    /**
     * {@inheritdoc}
     */
    public function on(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->compiler = $connection->factory()->compiler(static::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null): ResultSetInterface
    {
        try {
            return $this->connection->execute($this);
        } catch (DBALException $e) {
            if ($this->statements['mode'] === self::MODE_IGNORE && $e->getPrevious() instanceof BulkWriteException) {
                return new WriteResultSet($e->getPrevious()->getWriteResult());
            }

            //Encapsulation des exceptions de la couche basse.
            //Permet d'eviter la remonté des infos systèmes en cas de catch non intentionnel
            throw new DBALException('dbal internal error has occurred', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function into(string $table)
    {
        $this->compilerState->invalidate();

        // Reset columns when changing table
        $this->statements['columns'] = [];
        $this->statements['collection'] = $table;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function from($from, $alias = null)
    {
        return $this->into($from);
    }

    /**
     * {@inheritdoc}
     */
    public function columns(array $columns)
    {
        $this->compilerState->invalidate('columns');

        $this->statements['columns'] = [];

        foreach ($columns as $name => $type) {
            if (is_int($name)) {
                $this->statements['columns'][] = [
                    'name' => $type,
                    'type' => null
                ];
            } else {
                $this->statements['columns'][] = [
                    'name' => $name,
                    'type' => $type
                ];
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $data, bool $replace = false)
    {
        $this->compilerState->invalidate();

        if (empty($this->statements['columns'])) {
            $this->columns(array_keys($data));
        }

        if (!$this->statements['bulk'] || $replace) {
            $this->statements['values'] = [$data];
        } else {
            $this->statements['values'][] = $data;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mode(string $mode)
    {
        if ($mode !== $this->statements['mode']) {
            $this->compilerState->invalidate('mode');
            $this->statements['mode'] = $mode;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ignore(bool $flag = true)
    {
        return $this->mode($flag ? self::MODE_IGNORE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(bool $flag = true)
    {
        return $this->mode($flag ? self::MODE_REPLACE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(bool $flag = true)
    {
        $this->compilerState->invalidate();
        $this->statements['bulk'] = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(bool $forceRecompile = false)
    {
        return $this->statements['mode'] === self::MODE_REPLACE
            ? $this->compiler->compileUpdate($this)
            : $this->compiler->compileInsert($this)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return $this->statements['mode'] === self::MODE_REPLACE
            ? self::TYPE_UPDATE
            : self::TYPE_INSERT
        ;
    }
}
