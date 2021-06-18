<?php

namespace Bdf\Prime\MongoDB;

use Bdf\Config\Config;
use Bdf\Config\ConfigLoader;
use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Web\Application;

/**
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_PrimeMongoDbServiceProvider
 */
class PrimeMongoDbServiceProviderTest extends TestCase
{
    protected $config;
    protected $container;
    
    /**
     * 
     */
    protected function setUp()
    {
        if (!class_exists(Application::class)) {
            $this->markTestSkipped();
        }

        $this->container = new Application([
            'config'         => $this->config = new Config(),
            'config.loader'  => new ConfigLoader(),
        ]);
        
        $provider = new PrimeServiceProvider();
        $provider->configure($this->container);
        $provider = new PrimeMongoDbServiceProvider();
        $provider->configure($this->container);
    }

    /**
     * 
     */
    public function test_connection_manager()
    {
        $this->config->set('prime.connection', [
            'default'     => 'default-connection',
            'config'      => ['host' => 'localhost'],
            'environment' => 'env-test',
        ]);
        
        $manager = $this->container->get('prime-connectionManager');
        
        $this->assertEquals([MongoDriver::class, MongoConnection::class], $manager->getDriverMap('mongodb'));
    }
}
