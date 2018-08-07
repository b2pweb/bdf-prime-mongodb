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
use Bdf\Prime\MongoDB\Platform\Types\BsonObjectIdType;
use Bdf\Prime\MongoDB\Platform\Types\BsonObjectType;
use Bdf\Prime\MongoDB\Platform\Types\BsonStringType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypes;
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
    private $grammar;

    /**
     * @var PlatformTypes
     */
    private $types;


    /**
     * MongoPlatform constructor.
     *
     * @param AbstractPlatform $grammar
     * @param TypesRegistryInterface $commons
     */
    public function __construct(AbstractPlatform $grammar, TypesRegistryInterface $commons)
    {
        $this->grammar = $grammar;
        $this->types = new PlatformTypes(
            $this,
            [
                TypeInterface::TARRAY       => BsonArrayType::class,
                TypeInterface::BLOB         => BsonBinDataType::class,
                TypeInterface::BINARY       => BsonBinDataType::class,
                TypeInterface::BOOLEAN      => BsonBooleanType::class,
                TypeInterface::DATETIME     => BsonDateType::class,
                TypeInterface::DECIMAL      => BsonDecimalType::class,
                TypeInterface::FLOAT        => BsonDoubleType::class,
                TypeInterface::DOUBLE       => BsonDoubleType::class,
                TypeInterface::INTEGER      => BsonIntegerType::class,
                TypeInterface::SMALLINT     => BsonIntegerType::class,
                TypeInterface::TINYINT      => BsonIntegerType::class,
                TypeInterface::BIGINT       => BsonLongType::class,
                TypeInterface::OBJECT       => BsonObjectType::class,
                TypeInterface::JSON         => BsonObjectType::class,
                TypeInterface::ARRAY_OBJECT => BsonObjectType::class,
                TypeInterface::STRING       => BsonStringType::class,
                TypeInterface::TEXT         => BsonStringType::class,
                TypeInterface::GUID         => BsonObjectIdType::class,
            ],
            $commons
        );
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'mongodb';
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
    public function grammar()
    {
        return $this->grammar;
    }
}
