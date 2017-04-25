<?php

namespace Bdf\Prime\MongoDB\Odm;

use Bdf\Prime\IdGenerators\AbstractGenerator;
use Bdf\Prime\ServiceLocator;
use MongoDB\BSON\ObjectID;

/**
 * Id generator for MongoDB
 */
class MongoIdGenerator extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerate($primary, array &$data, ServiceLocator $serviceLocator)
    {
        return $data[$primary] = new ObjectID();
    }
}
