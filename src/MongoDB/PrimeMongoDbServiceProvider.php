<?php

namespace Bdf\Prime\MongoDB;

use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Web\Application;
use Bdf\Web\Providers\ServiceProviderInterface;

/**
 * Configuration de prime connection manager afin d'ajouter le map du driver
 *
 * @deprecated Do nothing since prime v1.1.0, will be deleted on v2.0.0
 */
class PrimeMongoDbServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Application $app)
    {
        // Prime <= 1.0.1
        if (method_exists(ConnectionManager::class, 'registerDriverMap')) {
            $app->extend('prime-connectionManager', function ($connections, $app) {
                $connections->registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

                return $connections;
            });
        }

        // Prime = v1.0.2
        if (class_exists(ConnectionFactory::class) && ConnectionFactory::getDriverMap('mongodb')) {
            ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);
        }
    }
}
