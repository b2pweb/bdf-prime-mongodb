<?php

namespace Bdf\Prime\MongoDB\Driver;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Factory\ConnectionFactoryInterface;
use Bdf\Prime\Exception\DBALException;

/**
 * Factory for create a MongoConnection
 *
 * This factory skip the Doctrine DBAL connection creation using {@see ConnectionFactory::registerDriverMap()},
 * so it should be registered before.
 *
 * This factory will handle DSN with protocol "mongodb" and "mongo"
 */
final class MongoConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(string $connectionName, array $parameters, ?Configuration $config = null): ConnectionInterface
    {
        $connection = new MongoConnection($parameters, $config ?? new Configuration());
        $connection->setName($connectionName);

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function support(string $connectionName, array $parameters): bool
    {
        return $parameters['driver'] === 'mongodb' || $parameters['driver'] === 'mongo';
    }
}
