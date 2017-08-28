<?php

namespace Bdf\Prime\MongoDB\Platform;

use Bdf\Prime\MongoDB\Platform\Types\BsonArrayType;
use Bdf\Prime\MongoDB\Platform\Types\BsonBinDataType;
use Bdf\Prime\MongoDB\Platform\Types\BsonBooleanType;
use Bdf\Prime\MongoDB\Platform\Types\BsonDateType;
use Bdf\Prime\MongoDB\Platform\Types\BsonDecimalType;
use Bdf\Prime\MongoDB\Platform\Types\BsonDoubleType;
use Bdf\Prime\MongoDB\Platform\Types\BsonIntegerType;
use Bdf\Prime\MongoDB\Platform\Types\BsonLongType;
use Bdf\Prime\MongoDB\Platform\Types\BsonObjectType;
use Bdf\Prime\MongoDB\Platform\Types\BsonStringType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypesRegistry;
use Bdf\Prime\Platform\Types\SqlDefaultType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Prime platform for MongoDB
 */
class MongoPlatform implements PlatformInterface
{
    /**
     * @var AbstractPlatform
     */
    private $doctrine;

    /**
     * @var PlatformTypesRegistry
     */
    protected $types;


    /**
     * MongoPlatform constructor.
     *
     * @param AbstractPlatform $doctrine
     */
    public function __construct(AbstractPlatform $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->types = new PlatformTypesRegistry(new SqlDefaultType());

        $this->registerTypes($this->types);
    }

    /**
     * {@inheritdoc}
     */
    public function types()
    {
        return $this->types;
    }


    /**
     * {@inheritdoc}
     */
    public function toDoctrinePlatform()
    {
        return $this->doctrine;
    }

    /**
     * Register custom types
     *
     * @param TypesRegistryInterface $types
     */
    protected function registerTypes(TypesRegistryInterface $types)
    {
        $types->register(BsonArrayType::class, TypeInterface::TARRAY);
        $types->register(BsonBinDataType::class, TypeInterface::BLOB);
        $types->register(BsonBooleanType::class, TypeInterface::BOOLEAN);
        $types->register(BsonDateType::class, TypeInterface::DATETIME);
        $types->register(BsonDecimalType::class, TypeInterface::DECIMAL);
        $types->register(BsonDoubleType::class, TypeInterface::FLOAT);
        $types->register(BsonDoubleType::class, TypeInterface::DOUBLE);
        $types->register(BsonIntegerType::class, TypeInterface::INTEGER);
        $types->register(BsonIntegerType::class, TypeInterface::SMALLINT);
        $types->register(BsonIntegerType::class, TypeInterface::TINYINT);
        $types->register(BsonLongType::class, TypeInterface::BIGINT);
        $types->register(BsonObjectType::class, TypeInterface::OBJECT);
        $types->register(BsonStringType::class, TypeInterface::STRING);
        $types->register(BsonStringType::class, TypeInterface::TEXT);
    }
}
