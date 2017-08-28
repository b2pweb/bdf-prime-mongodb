<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\Schema\AbstractSchemaManager;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;
use Bdf\Prime\Schema\Comparator\IndexSetComparator;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\TableInterface;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;

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
    public function flush()
    {
        foreach ($this->pending as $pending) {
            if ($pending instanceof Command) {
                $this->connection->runCommand($pending);
            } elseif ($pending instanceof \Closure) {
                $pending($this->connection);
            }
        }

        $this->pending = [];
    }

    /**
     * {@inheritdoc}
     */
    public function pending()
    {
        return $this->pending;
    }

    /**
     * {@inheritdoc}
     */
    public function schema($tables = [])
    {
        if (!is_array($tables)) {
            $tables = [$tables];
        }

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
    public function hasDatabase($database)
    {
        return in_array(strtolower($database), array_map('strtolower', $this->getDatabases()));
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabases()
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
        return $this->pushCommand('dropDatabase');
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        $cursor = $this->connection->runCommand([
            'listCollections' => 1,
            'filter'          => [
                'name' => $tableName
            ]
        ]);

        return !empty($cursor->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function loadTable($tableName)
    {
        return new Table(
            $tableName,
            [], //Cannot resolve schemas from MongoDB
            new IndexSet($this->getIndexes($tableName))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function drop($tableName)
    {
        return $this->pushCommand('drop', $tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function truncate($tableName, $cascade = false)
    {
        return $this->push(function (MongoConnection $connection) use($tableName) {
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
        return $this->push(function (MongoConnection $connection) use($from, $to) {
            $connection->runAdminCommand([
                'renameCollection' => $this->connection->getDatabase().'.'.$from,
                'to'               => $this->connection->getDatabase().'.'.$to
            ]);
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

    /**
     * @param string|array|Command $command
     * @param mixed $arguments
     *
     * @return $this
     */
    protected function pushCommand($command, $arguments = 1)
    {
        if (is_array($command)) {
            $command = new Command($command);
        } elseif (is_string($command)) {
            $command = new Command([$command => $arguments]);
        }

        return $this->push($command);
    }
}
