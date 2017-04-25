<?php

namespace Bdf\Prime\MongoDB\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use MongoDB\Driver\Manager;

/**
 * Driver for @see MongoConnection
 */
class MongoDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        $uri = 'mongodb://'.$params['host'];

        if (!empty($params['port'])) {
            $uri .= ':'.$params['port'];
        }

        return new Manager($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new MongoPlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn)
    {
        return new MongoSchemasManager($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mongodb';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        return $conn->getParams()['dbname'];
    }
}
