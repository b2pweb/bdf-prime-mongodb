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
        if (!empty($username)) {
            $params['username'] = $username;
        }

        if (!empty($password)) {
            $params['password'] = $password;
        }

        if (!empty($params['noAuth'])) {
            unset($params['username'], $params['password']);
        }

        return new Manager($this->buildDsn($params), array_filter($params), $driverOptions);
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

    private function buildDsn(array $params): string
    {
        $uri = 'mongodb://';

        if (isset($params['host'])) {
            $uri .= $params['host'];

            if (!empty($params['port'])) {
                $uri .= ':' . $params['port'];
            }

            return $uri;
        }

        if (isset($params['hosts'])) {
            $uri .= implode(',', $params['hosts']);

            return $uri;
        }

        throw new \InvalidArgumentException('Cannot build mongodb DSN');
    }
}
