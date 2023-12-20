<?php

namespace Bdf\Prime\MongoDB\Driver;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\MongoDB\Driver\Exception\MongoCommandException;
use Bdf\Prime\MongoDB\Driver\Exception\MongoDBALException;
use Bdf\Prime\MongoDB\Driver\ResultSet\CursorResultSet;
use Bdf\Prime\MongoDB\Platform\MongoPlatform as PrimePlatform;
use Bdf\Prime\MongoDB\Query\Aggregation\Pipeline;
use Bdf\Prime\MongoDB\Query\Aggregation\PipelineCompiler;
use Bdf\Prime\MongoDB\Query\Aggregation\PipelineInterface;
use Bdf\Prime\MongoDB\Query\Command\CommandInterface;
use Bdf\Prime\MongoDB\Query\Command\Commands;
use Bdf\Prime\MongoDB\Query\Compiler\MongoCompiler;
use Bdf\Prime\MongoDB\Query\Compiler\MongoInsertCompiler;
use Bdf\Prime\MongoDB\Query\Compiler\MongoKeyValueCompiler;
use Bdf\Prime\MongoDB\Query\MongoInsertQuery;
use Bdf\Prime\MongoDB\Query\MongoKeyValueQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\MongoDB\Query\SelfExecutable;
use Bdf\Prime\MongoDB\Schema\MongoSchemaManager as PrimeSchemaManager;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Factory\DefaultQueryFactory;
use Bdf\Prime\Query\Factory\QueryFactoryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Closure;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Statement;
use InvalidArgumentException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Exception\CommandException;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteResult;

use function array_filter;
use function implode;

/**
 * Connection for mongoDb
 *
 * @method \Bdf\Prime\Configuration getConfiguration()
 * @final
 */
class MongoConnection extends Connection implements ConnectionInterface
{
    protected string $name = '';
    protected ?PrimeSchemaManager $schema = null;

    /**
     * Field for emulate transaction into MongoDB (will be added on each document)
     *
     * @var string
     */
    protected string $transactionEmulationStateField = '__MONGO_CONNECTION_TRANSACTION__';

    /**
     * Level for transaction emulation
     *
     * @var int
     */
    protected int $transationLevel = 0;

    protected ?PrimePlatform $platform = null;
    protected QueryFactoryInterface $factory;

    /**
     * The mongoDB connection
     * Do not use directly, use $this->connection() instead
     */
    private ?Manager $connection = null;
    private array $parameters;

    /**
     * @param $params
     * @param Driver|Configuration|null $driver
     * @param Configuration|null $config
     * @param EventManager|null $eventManager
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct($params, $driver = null, ?Configuration $config = null, EventManager $eventManager = null)
    {
        if ($config === null && $driver instanceof Configuration) {
            $config = $driver;
            $driver = null;
        }

        parent::__construct($params, $driver ?? new MongoDriver(), $config, $eventManager);

        $this->parameters = $params;

        /** @psalm-suppress InvalidArgument */
        $this->factory = new DefaultQueryFactory(
            $this,
            new MongoCompiler($this),
            [
                MongoKeyValueQuery::class => MongoKeyValueCompiler::class,
                MongoInsertQuery::class   => MongoInsertCompiler::class,
                Pipeline::class           => PipelineCompiler::class,
            ],
            [
                KeyValueQueryInterface::class => MongoKeyValueQuery::class,
                InsertQueryInterface::class   => MongoInsertQuery::class,
                PipelineInterface::class      => Pipeline::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(): string
    {
        return $this->parameters['dbname'];
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): PrimeSchemaManager
    {
        if ($this->schema === null) {
            $this->schema = new PrimeSchemaManager($this);
        }

        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function platform(): PlatformInterface
    {
        if ($this->platform === null) {
            $this->platform = new PrimePlatform(
                new MongoPlatform(),
                $this->getConfiguration()->getTypes()
            );
        }

        return $this->platform;
    }

    /**
     * {@inheritdoc}
     */
    public function make($query, PreprocessorInterface $preprocessor = null): \Bdf\Prime\Query\CommandInterface
    {
        return $this->factory->make($query, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function factory(): QueryFactoryInterface
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     */
    public function from($table, ?string $alias = null): ReadCommandInterface
    {
        return $this->builder()->from($table);
    }

    /**
     * {@inheritdoc}
     */
    public function select($query, array $bindings = [], array $types = []): ResultSetInterface
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $data, array $types = [])
    {
        return $this->builder()->from($table)->insert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, $type, array $fieldOptions = [])
    {
        return $this->platform()->types()->fromDatabase($value, $type, $fieldOptions);
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
     * @return Cursor
     * @throws MongoDBALException
     */
    public function executeSelect($collection, Query $query): Cursor
    {
        try {
            $cursor = $this->connection()->executeQuery($this->getNamespace($collection), $query);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

            return $cursor;
        } catch (Exception $e) {
            throw new MongoDBALException('MongoDB : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $collection
     * @param BulkWrite $query
     *
     * @return WriteResult
     * @throws MongoDBALException
     */
    public function executeWrite($collection, BulkWrite $query): WriteResult
    {
        try {
            return $this->connection()->executeBulkWrite($this->getNamespace($collection), $query);
        } catch (Exception $e) {
            throw new MongoDBALException('MongoDB : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($sql, array $params = [], $types = [], QueryCacheProfile $qcp = null): Result
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($sql, array $params = [], array $types = []): int
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $sql): Statement
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        throw new \BadMethodCallException('Method ' . __METHOD__ . ' cannot be called on mongoDB connection');
    }

    /**
     * Run a command
     *
     * @param mixed $command
     * @param mixed $arguments
     *
     * @return Cursor
     * @throws MongoDBALException
     */
    public function runCommand($command, $arguments = 1): Cursor
    {
        try {
            return $this->connection()->executeCommand(
                $this->getDatabase(),
                Commands::create($command, $arguments)->get()
            );
        } catch (CommandException $e) {
            throw new MongoCommandException($e);
        } catch (Exception $e) {
            throw new MongoDBALException('MongoDB : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Run a command
     *
     * @param string|array|Command|CommandInterface $command
     * @param mixed $arguments
     *
     * @return \MongoDB\Driver\Cursor
     */
    public function runAdminCommand($command, $arguments = 1)
    {
        try {
            return $this->connection()->executeCommand(
                'admin',
                Commands::create($command, $arguments)->get()
            );
        } catch (CommandException $e) {
            throw new MongoCommandException($e);
        } catch (Exception $e) {
            throw new MongoDBALException('MongoDB : ' . $e->getMessage(), 0, $e);
        }
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

        foreach ($this->schema()->getCollections() as $collection) {
            $bulk = new BulkWrite();
            $bulk->update([], [
                '$inc' => [
                    $this->transactionEmulationStateField => 1
                ]
            ], ['multi' => true]);

            $this->executeWrite($collection, $bulk);
        }

        ++$this->transationLevel;

        return true;
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

        foreach ($this->schema()->getCollections() as $collection) {
            $bulk = new BulkWrite();
            $bulk->update([], [
                '$inc' => [
                    $this->transactionEmulationStateField => -1
                ]
            ], ['multi' => true]);

            $this->executeWrite($collection, $bulk);
        }

        --$this->transationLevel;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        if ($this->transationLevel === 0) {
            throw ConnectionException::noActiveTransaction();
        }

        foreach ($this->schema()->getCollections() as $collection) {
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

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function builder(PreprocessorInterface $preprocessor = null): ReadCommandInterface
    {
        /** @psalm-suppress InvalidArgument */
        return $this->factory->make(MongoQuery::class, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Compilable $query): ResultSetInterface
    {
        $compiled = $query->compile();

        if ($compiled instanceof SelfExecutable) {
            return $compiled->execute($this);
        }

        if ($compiled instanceof CommandInterface || $compiled instanceof Command) {
            return new CursorResultSet($this->runCommand($compiled));
        }

        throw new InvalidArgumentException('Unsupported compiled query type ' . get_class($compiled));
    }

    /**
     * @param string $collection
     *
     * @return string
     */
    protected function getNamespace($collection)
    {
        return $this->getDatabase() . '.' . $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();

        if ($this->connection !== null) {
            $this->connection = null;
        }
    }

    private function connection(): Manager
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $params = $this->parameters;
        $dsn = $this->buildDsn($params);

        return $this->connection = new Manager($dsn, array_filter($params));
    }

    private function buildDsn(array $params): string
    {
        $uri = 'mongodb://';

        if (!empty($params['host'])) {
            $uri .= $params['host'];

            if (!empty($params['port'])) {
                $uri .= ':' . $params['port'];
            }

            return $uri;
        }

        if (!empty($params['hosts'])) {
            $uri .= implode(',', $params['hosts']);

            return $uri;
        }

        throw new InvalidArgumentException('Cannot build mongodb DSN');
    }

    // Mark all methods of doctrine's connection as deprecated

    /**
     * @deprecated
     */
    public function getDriver()
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::getDriver();
    }

    /**
     * @deprecated
     */
    public function getDatabasePlatform()
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::getDatabasePlatform();
    }

    /**
     * @deprecated
     */
    public function createExpressionBuilder(): ExpressionBuilder
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::createExpressionBuilder();
    }

    /**
     * @deprecated
     */
    public function isConnected()
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::isConnected();
    }

    /**
     * @deprecated
     */
    public function transactional(Closure $func)
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::transactional($func);
    }

    /**
     * @deprecated
     */
    public function getNativeConnection()
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::getNativeConnection();
    }

    /**
     * @deprecated
     */
    public function createSchemaManager(): AbstractSchemaManager
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::createSchemaManager();
    }

    /**
     * @deprecated
     */
    public function convertToDatabaseValue($value, $type)
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::convertToDatabaseValue($value, $type);
    }

    /**
     * @deprecated
     */
    public function convertToPHPValue($value, $type)
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated without replacement.', E_USER_DEPRECATED);
        return parent::convertToPHPValue($value, $type);
    }
}
