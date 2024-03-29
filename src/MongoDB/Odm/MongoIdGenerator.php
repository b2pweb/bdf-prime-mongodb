<?php

namespace Bdf\Prime\MongoDB\Odm;

use Bdf\Prime\IdGenerators\AbstractGenerator;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\ServiceLocator;
use MongoDB\BSON\ObjectId;

/**
 * Id generator for MongoDB
 * @deprecated
 * @extends AbstractGenerator<MongoConnection>
 */
class MongoIdGenerator extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerate($property, array &$data, ServiceLocator $serviceLocator)
    {
        return $data[$property] = new ObjectId();
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess($entity): void
    {
        // Keep old algorithm to ensure that it'll not cause BC breaks
        if (!$this->hasBeenErased) {
            return;
        }

        $propertyName = $this->getPropertyToHydrate();
        $value = $this->lastGeneratedId();

        $this->mapper()->hydrateOne($entity, $propertyName, $value);
    }
}
