<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Bdf\Prime\Serializer\PaginatorNormalizer;
use Bdf\Prime\Serializer\PrimeCollectionNormalizer;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\Types\ArrayType;
use Bdf\Serializer\Normalizer\ObjectNormalizer;
use Bdf\Serializer\SerializerBuilder;

/**
 * PrimeTestCase
 */
trait PrimeTestCase
{
    /**
     *
     */
    public function prime()
    {
        return Prime::service();
    }

    /**
     *
     */
    public function pack()
    {
        return TestPack::pack();
    }

    /**
     * 
     */
    public function configurePrime()
    {
        if (!Prime::isConfigured()) {
            Prime::configure([
//                'logger' => new PsrDecorator(new Logger()),
//                'resultCache' => new \Bdf\Prime\Cache\ArrayCache(),
                'connection' => [
                    'config' => [
                        'test' => [
                            'adapter' => 'sqlite',
                            'memory' => true
                        ],
                    ]
                ],
                'types' => [
                    'searchable_array' => ArrayType::class,
                    new AddressType(),
                ],
            ]);
            ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

            $serializer = SerializerBuilder::create()
                ->build();

            $serializer->getLoader()
                ->addNormalizer(new PrimeCollectionNormalizer(Prime::service()))
                ->addNormalizer(new PaginatorNormalizer())
                ->addNormalizer(new ObjectNormalizer())
            ;

            Prime::service()->setSerializer($serializer);

            Model::configure(Prime::service());
        }
    }

    /**
     *
     */
    public function unsetPrime()
    {
        Prime::configure(null);
        Model::configure(null);
    }

    /**
     *
     */
    public function primeStart()
    {
        $this->configurePrime();

        if (method_exists($this, 'declareTestData')) {
            $this->declareTestData(TestPack::pack());
        }

        TestPack::pack()->initialize();
    }

    /**
     *
     */
    public function primeReset()
    {
        TestPack::pack()->clear();
    }

    /**
     *
     */
    public function primeStop()
    {
        TestPack::pack()->destroy();
    }
}
