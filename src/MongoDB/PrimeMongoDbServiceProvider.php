<?php

namespace Bdf\Prime\MongoDB;

use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Web\Application;
use Bdf\Web\Providers\ServiceProviderInterface;

/**
 * Configuration de prime connection manager afin d'ajouter le map du driver
 */
class PrimeMongoDbServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Application $app)
    {
        /**
         * ConnectionManager
         */
        $app->extend('prime-connectionManager', function($connections, $app) {
            $connections->registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);
            
            return $connections;
        });
    }
}
