<?php

namespace Bdf\Prime\MongoDB\Driver;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Query\Command\Commands;
use Bdf\Prime\MongoDB\Schema\MongoSchemaManager as PrimeSchemaManager;
use Bdf\Prime\MongoDB\Platform\MongoPlatform as PrimePlatform;
use Bdf\Prime\MongoDB\Query\MongoCompiler;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;

/**
 * Connection for mongoDb
 */
class MongoConnection extends Connection implements ConnectionInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Manager
     */
    protected $_conn;

    /**
     * @var PrimeSchemaManager
     */
    protected $schema;

    /**
     * Field for emulate transaction into MongoDB (will be added on
     *
     * @var string
     */
    protected $transactionEmulationStateField = '__MONGO_CONNECTION_TRANSACTION__';

    /**
     * Level for transaction emulation
     *
     * @var int
     */
    protected $transationLevel = 0;

    /**
     * @var PrimePlatform
     */
    protected $platform;


    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        if ($this->schema === null) {
            $this->schema = new PrimeSchemaManager($this);
        }

        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function platform()
    {
        if ($this->platform === null) {
            $this->platform = new PrimePlatform(
                $this->getDatabasePlatform(),
                $this->getConfiguration()->getTypes()
            );
        }

        return $this->platform;
    }

    /**
     * {@inheritdoc}
     */
    public function from($table)
    {
        return $this->builder()->from($table);
    }

    /**
     * {@inheritdoc}
     */
    public function select($query, array $bindings = [], array $types = [])
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, $type)
    {
        return $this->platform()->types()->fromDatabase($value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value, $type = null)
    {
        return $this->platform()->types()->toDatabase($value, $type);
    }

    /**
     * @param string $collection
     * @param Query $query
     *
     * @return \MongoDB\Driver\Cursor
     */
    public function executeSelect($collection, Query $query)
    {
        try {
            $this->connect();

            $cursor = $this->_conn->executeQuery($this->getNamespace($collection), $query);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

            return $cursor;
        } catch (\Exception $e) {
            throw new DBALException('MongoDB : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $collection
     * @param BulkWrite $query
     *
     * @return \MongoDB\Driver\WriteResult
     */
    public function executeWrite($collection, BulkWrite $query)
    {
        try {
            $this->connect();

            return $this->_conn->executeBulkWrite($this->getNamespace($collection), $query);
        } catch (\Exception $e) {
            throw new DBALException('MongoDB : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null)
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * Run a command
     *
     * @param mixed $command
     * @param mixed $arguments
     *
     * @return \MongoDB\Driver\Cursor
     */
    public function runCommand($command, $arguments = 1)
    {
        $this->connect();

        return $this->_conn->executeCommand(
            $this->getDatabase(),
            Commands::create($command, $arguments)->get()
        );
    }

    /**
     * Run a command
     *
     * @param string|array|Command $command
     * @param mixed $arguments
     *
     * @return \MongoDB\Driver\Cursor
     */
    public function runAdminCommand($command, $arguments = 1)
    {
        $this->connect();

        return $this->_conn->executeCommand(
            'admin',
            Commands::create($command, $arguments)->get()
        );
    }

    /**
     * Drop the current database
     */
    public function dropDatabase()
    {
        $this->runCommand('dropDatabase');
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->connect();

        foreach ($this->getSchemaManager()->listTableNames() as $collection) {
            $bulk = new BulkWrite();
            $bulk->update([], [
                '$inc' => [
                    $this->transactionEmulationStateField => 1
                ]
            ], ['multi' => true]);

            $this->executeWrite($collection, $bulk);
        }

        ++$this->transationLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints)
    {
        //Do nothing : this behavior is emulated
    }

    /**
     * {@inheritdoc}
     */
    public function isTransactionActive()
    {
        return $this->transationLevel > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        if ($this->transationLevel === 0) {
            throw ConnectionException::noActiveTransaction();
        }

        foreach ($this->getSchemaManager()->listTableNames() as $collection) {
            $bulk = new BulkWrite();
            $bulk->update([], [
                '$inc' => [
                    $this->transactionEmulationStateField => -1
                ]
            ], ['multi' => true]);

            $this->executeWrite($collection, $bulk);
        }

        --$this->transationLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        if ($this->transationLevel === 0) {
            throw ConnectionException::noActiveTransaction();
        }

        foreach ($this->getSchemaManager()->listTableNames() as $collection) {
            $bulk = new BulkWrite();

            $bulk->delete([
                '$or' => [
                    [$this->transactionEmulationStateField => ['$exists' => false]],
                    [$this->transactionEmulationStateField => ['$lte'    => 0]]
                ]
            ]);
            $bulk->update([], [
                '$inc' => [
                    $this->transactionEmulationStateField => -1
                ]
            ], ['multi' => true]);

            $this->executeWrite($collection, $bulk);
        }

        --$this->transationLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function builder(PreprocessorInterface $preprocessor = null)
    {
        return new MongoQuery($this, new MongoCompiler($preprocessor));
    }

    /**
     * @param string $collection
     *
     * @return string
     */
    protected function getNamespace($collection)
    {
        return $this->getDatabase().'.'.$collection;
    }
}
