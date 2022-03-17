<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\Command\CommandInterface;
use Bdf\Prime\MongoDB\Query\Command\Drop;
use Bdf\Prime\MongoDB\Query\Command\DropDatabase;
use Bdf\Prime\MongoDB\Query\Command\ListCollections;
use Bdf\Prime\MongoDB\Query\Command\RenameCollection;
use Bdf\Prime\Schema\AbstractSchemaManager;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;
use Bdf\Prime\Schema\Comparator\IndexSetComparator;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\TableInterface;
use MongoDB\Driver\BulkWrite;

/**
 * SchemaManager for MongoDB on Prime
 *
 * @property MongoConnection $connection protected
 */
class MongoSchemaManager extends AbstractSchemaManager
{
    /**
     * @var array
     */
    protected $pending = [];


    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->pending = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        foreach ($this->pending as $pending) {
            if ($pending instanceof CommandInterface) {
                $this->connection->runCommand($pending);
            } elseif ($pending instanceof \Closure) {
                $pending($this->connection);
            }
        }

        $this->pending = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function pending(): array
    {
        return $this->pending;
    }

    /**
     * {@inheritdoc}
     *
     * @param TableInterface[] $tables
     *
     * @psalm-suppress InvalidReturnType
     */
    public function schema($tables = [])
    {
        if (!is_array($tables)) {
            $tables = [$tables];
        }

        /** @psalm-suppress InvalidReturnStatement */
        return new SchemaCreation($tables);
    }

    /**
     * {@inheritdoc}
     */
    public function diff(TableInterface $newTable, TableInterface $oldTable)
    {
        $comparator = new IndexSetComparator(
            $oldTable->indexes(),
            $newTable->indexes()
        );

        return new IndexSetDiff(
            $newTable->name(),
            $comparator
        );
    }

    /**
     * {@inheritdoc}
     *
     * @todo à voir si on a un système de schema
     */
    public function loadSchema()
    {
        throw new \BadMethodCallException('Cannot load Doctrine Schema from MongoDB');
//        $tables = [];
//
//        foreach ($this->connection->runCommand('listCollections') as $collection) {
//            $tables[] = $this->loadTable($collection->name);
//        }
//
//        $config = new SchemaConfig();
//
//        $config->setName($this->connection->getDatabase());
//
//        return new Schema(
//            $tables,
//            [],
//            $config
//        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasDatabase($database): bool
    {
        return in_array(strtolower($database), array_map('strtolower', $this->getDatabases()));
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabases(): array
    {
        $list = $this->connection->runAdminCommand('listDatabases')->toArray();

        $collections = [];

        foreach ($list[0]->databases as $info) {
            $collections[] = $info->name;
        }

        return $collections;
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabase($database)
    {
        // Databases are implicitly created
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database)
    {
        return $this->push(new DropDatabase());
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName): bool
    {
        $cursor = $this->connection->runCommand(
            (new ListCollections())
                ->byName($tableName)
        );

        return !empty($cursor->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function loadTable($tableName): Table
    {
        $cursor = $this->connection->runCommand((new ListCollections())->byName($tableName));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        $collection = $cursor->toArray()[0] ?? [];

        return new Table(
            $tableName,
            [], //Cannot resolve schemas from MongoDB
            new IndexSet($this->getIndexes($tableName)),
            null,
            $collection['options'] ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function drop($tableName)
    {
        return $this->push(new Drop($tableName));
    }

    /**
     * {@inheritdoc}
     */
    public function truncate($tableName, $cascade = false)
    {
        return $this->push(function (MongoConnection $connection) use ($tableName) {
            $bulk = new BulkWrite();
            $bulk->delete([]);

            $connection->executeWrite($tableName, $bulk);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function rename($from, $to)
    {
        return $this->push(function (MongoConnection $connection) use ($from, $to) {
            $connection->runAdminCommand(new RenameCollection(
                $this->connection->getDatabase() . '.' . $from,
                $this->connection->getDatabase() . '.' . $to
            ));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function push($queries)
    {
        if ($queries instanceof CommandSetInterface) {
            $queries = $queries->commands();
        } elseif (!is_array($queries)) {
            $queries = [$queries];
        }

        foreach ($queries as $query) {
            $this->pending[] = $query;
        }

        if ($this->autoFlush) {
            $this->flush();
        }

        return $this;
    }

    /**
     * @param string $table
     *
     * @return IndexInterface[]
     */
    protected function getIndexes($table)
    {
        $indexes = [];

        $cursor = $this->connection->runCommand('listIndexes', $table);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        foreach ($cursor as $info) {
            $indexes[] = new MongoIndex($info);
        }

        return $indexes;
    }
}
