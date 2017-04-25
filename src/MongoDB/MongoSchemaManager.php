<?php

namespace Bdf\Prime\MongoDB;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\Schema\AbstractSchemaManager;
use Bdf\Prime\Schema\Table;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\SchemaDiff;
use MongoDB\Driver\BulkWrite;

/**
 * SchemaManager for MongoDB on Prime
 *
 * @property MongoConnection $connection protected
 */
class MongoSchemaManager extends AbstractSchemaManager
{
    /**
     * @var Schema[]|SchemaDiff[]
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
            if ($pending instanceof Schema) {
                $this->executeSchema($pending);
            } elseif ($pending instanceof SchemaDiff) {
                $this->executeDiff($pending);
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
     *
     */
    protected function executeSchema(Schema $schema)
    {
        foreach ($schema->getTables() as $table) {
            $this->executeIndexes($table->getName(), $table->getIndexes());
        }
    }

    /**
     * @param SchemaDiff $diff
     */
    protected function executeDiff(SchemaDiff $diff)
    {
        foreach ($diff->changedTables as $table) {
            $this->executeIndexes($table->name, array_merge($table->addedIndexes, $table->changedIndexes));

            foreach ($table->removedIndexes as $index) {
                $this->connection->runCommand([
                    'dropIndexes' => $table->name,
                    'index'       => $index->getName()
                ]);
            }
        }

        foreach ($diff->newTables as $table) {
            $this->connection->runCommand('create', $table->getName());
            $this->executeIndexes($table->getName(), $table->getIndexes());
        }

        foreach ($diff->removedTables as $table) {
            $this->drop($table->getName());
        }
    }

    /**
     * @param string $collection
     * @param Index[] $indexes
     */
    protected function executeIndexes($collection, array $indexes)
    {
        $indexQuery = [];

        foreach ($indexes as $index) {
            $indexQuery[] = [
                'key'  => array_fill_keys($index->getColumns(), 1),
                'name' => $index->getName()
            ];
        }

        $this->connection->runCommand([
            'createIndexes' => $collection,
            'indexes'       => $indexQuery
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadSchema()
    {
        $tables = [];

        foreach ($this->connection->runCommand('listCollections') as $collection) {
            $tables[] = $this->loadTable($collection->name);
        }

        $config = new SchemaConfig();

        $config->setName($this->connection->getDatabase());

        return new Schema(
            $tables,
            [],
            $config
        );
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
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database)
    {
        $this->connection->dropDatabase();
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
            $this->getIndexes($tableName)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function drop($tableName)
    {
        $this->connection->runCommand('drop', $tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function truncate($tableName, $cascade = false)
    {
        $bulk = new BulkWrite();
        $bulk->delete([]);

        $this->connection->executeWrite($tableName, $bulk);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($from, $to)
    {
        $this->connection->runAdminCommand([
            'renameCollection' => $this->connection->getDatabase().'.'.$from,
            'to'               => $this->connection->getDatabase().'.'.$to
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function push($queries)
    {
        if (!is_array($queries)) {
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
     * @return Index[]
     */
    protected function getIndexes($table)
    {
        $indexes = [];

        $cursor = $this->connection->runCommand('listIndexes', $table);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        foreach ($cursor as $info) {
            $indexes[] = new Index(
                $info['name'],
                array_keys($info['key']),
                !empty($info['unique']),
                $info['name'] === '_id_' //Primary index is on _id_ index
            );
        }

        return $indexes;
    }
}
