<?php

namespace Bdf\Prime\MongoDB\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query;
use MongoDB\Driver\Manager;

/**
 * Driver for @see MongoConnection
 *
 * @deprecated
 */
class MongoDriver implements Driver
{
    /**
     * {@inheritdoc}
     *
     * @psalm-suppress InvalidReturnType
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        @trigger_error('MongoDriver is deprecated. Use MongoConnection directly.', E_USER_DEPRECATED);

        if (!empty($username)) {
            $params['username'] = $username;
        }

        if (!empty($password)) {
            $params['password'] = $password;
        }

        if (!empty($params['noAuth'])) {
            unset($params['username'], $params['password']);
        }

        /** @psalm-suppress InvalidReturnStatement */
        return new Manager($this->buildDsn($params), array_filter($params), $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        @trigger_error('MongoDriver is deprecated. Use MongoConnection directly.', E_USER_DEPRECATED);

        return new MongoPlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        @trigger_error('MongoDriver is deprecated. Use MongoConnection directly.', E_USER_DEPRECATED);

        return new MongoSchemasManager($conn, $platform);
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

    /**
     * {@inheritdoc}
     */
    public function getExceptionConverter(): ExceptionConverter
    {
        return new class implements ExceptionConverter {
            public function convert(Exception $exception, ?Query $query): DriverException
            {
                return new DriverException($exception, $query);
            }
        };
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

        throw new \InvalidArgumentException('Cannot build mongodb DSN');
    }
}
