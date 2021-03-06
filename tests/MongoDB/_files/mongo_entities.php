<?php

namespace Bdf\Prime\MongoDB\Test;

use Bdf\Prime\Behaviors\Timestampable;
use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\MongoDB\Odm\MongoIdGenerator;
use MongoDB\BSON\ObjectId;

/**
 * Class Person
 */
class Person extends Model
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var int
     */
    protected $age;

    /**
     * @var array
     */
    protected $tags = [];


    /**
     * PersonEntity constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }

    /**
     * @return object
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function firstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = (string) $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function lastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = (string) $lastName;

        return $this;
    }

    /**
     * @return int
     */
    public function age()
    {
        return $this->age;
    }

    /**
     * @param int $age
     *
     * @return $this
     */
    public function setAge($age)
    {
        $this->age = (int) $age;

        return $this;
    }

    /**
     * @return array
     */
    public function tags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     *
     * @return $this
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }
}

/**
 * Class PersonMapper
 */
class PersonMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->setGenerator(MongoIdGenerator::class);
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'mongo',
            'table' => 'person_test'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildIndexes(IndexBuilder $builder): void
    {
        $builder->add('age_sort')->on('age');
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder): void
    {
        $builder
            ->guid('id')->alias('_id')->primary()
            ->string('firstName')->alias('first_name')->unique('search_name')
            ->string('lastName')->alias('last_name')->unique('search_name')
            ->integer('age')
            ->simpleArray('tags')->nillable()
        ;
    }
}

/**
 * Class Home
 */
class Home extends Model implements InitializableInterface
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Person
     */
    protected $proprietary;


    /**
     * Home constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->initialize();
        $this->import($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        $this->proprietary = new Person();
        $this->address = new Address();
    }

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Address
     */
    public function address()
    {
        return $this->address;
    }

    /**
     * @param Address $address
     *
     * @return $this
     */
    public function setAddress(Address $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Person
     */
    public function proprietary()
    {
        return $this->proprietary;
    }

    /**
     * @param Person $proprietary
     *
     * @return $this
     */
    public function setProprietary(Person $proprietary)
    {
        $this->proprietary = $proprietary;

        return $this;
    }
}

/**
 * Class HomeMapper
 */
class HomeMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->setGenerator(MongoIdGenerator::class);
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'mongo',
            'table' => 'home_test'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder): void
    {
        $builder
            ->string('id')->alias('_id')->primary()
            ->embedded('address', Address::class, function (FieldBuilder $builder) {
                $builder
                    ->string('address')->alias('address_address')
                    ->string('city')->alias('address_city')
                    ->string('zipCode')->alias('address_zipCode')
                    ->string('country')->alias('address_country')
                ;
            })
            ->embedded('proprietary', Person::class, function (FieldBuilder $builder) {
                $builder->string('id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations($builder): void
    {
        $builder
            ->on('proprietary')
            ->belongsTo(Person::class, 'proprietary.id')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildIndexes(IndexBuilder $builder): void
    {
        $builder
            ->add('search')
                ->on('address', ['type' => 'text'])
                ->on('city', ['type' => 'text'])
                ->option('weights', [
                    'city' => 2,
                    'address' => 1,
                ])
        ;
    }
}

/**
 * Class Address
 */
class Address {
    use ArrayInjector;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $zipCode;

    /**
     * @var string
     */
    protected $country;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }

    /**
     * @return string
     */
    public function address()
    {
        return $this->address;
    }

    /**
     * @param string $address
     *
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = (string) $address;

        return $this;
    }

    /**
     * @return string
     */
    public function city()
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = (string) $city;

        return $this;
    }

    /**
     * @return string
     */
    public function zipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     *
     * @return $this
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = (string) $zipCode;

        return $this;
    }

    /**
     * @return string
     */
    public function country()
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = (string) $country;

        return $this;
    }
}

/**
 * Class Home
 */
class EntityWithEmbedded extends Model implements InitializableInterface
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Person
     */
    protected $proprietary;


    /**
     * EntityWithEmbedded constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->initialize();
        $this->import($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        $this->proprietary = new Person();
        $this->address = new Address();
    }

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Address
     */
    public function address()
    {
        return $this->address;
    }

    /**
     * @param Address $address
     *
     * @return $this
     */
    public function setAddress(Address $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Person
     */
    public function proprietary()
    {
        return $this->proprietary;
    }

    /**
     * @param Person $proprietary
     *
     * @return $this
     */
    public function setProprietary(Person $proprietary)
    {
        $this->proprietary = $proprietary;

        return $this;
    }
}

/**
 * Class EntityWithEmbeddedMapper
 */
class EntityWithEmbeddedMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->setGenerator(MongoIdGenerator::class);
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'mongo',
            'table' => 'embedded_test'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder): void
    {
        $builder
            ->string('id')->alias('_id')->primary()
            ->embedded('address', Address::class, function (FieldBuilder $builder) {
                $builder
                    ->string('address')
                    ->string('city')
                    ->string('zipCode')
                    ->string('country')
                ;
            })
            ->embedded('proprietary', Person::class, function (FieldBuilder $builder) {
                $builder->string('id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations($builder): void
    {
        $builder
            ->on('proprietary')
            ->belongsTo(Person::class, 'proprietary.id')
        ;
    }
}

class TimestampableEntity extends Model
{
    protected $oid;
    protected $value;
    protected $createdAt;
    protected $updatedAt;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }

    /**
     * @return mixed
     */
    public function oid()
    {
        return $this->oid;
    }

    /**
     * @param mixed $oid
     *
     * @return $this
     */
    public function setOid($oid)
    {
        $this->oid = $oid;

        return $this;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function createdAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function updatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}

/**
 * Class TimestampableEntityMapper
 */
class TimestampableEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->setGenerator(MongoIdGenerator::class);
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'mongo',
            'table' => 'timestampable'
        ];
    }

    public function buildFields($builder): void
    {
        $builder
            ->guid('oid')->alias('_id')->primary()
            ->string('value')
        ;
    }

    public function getDefinedBehaviors(): array
    {
        return [
            new Timestampable()
        ];
    }
}

class EntityWithComplexArray extends Model
{
    public $oid;
    public $addresses;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class EntityWithComplexArrayMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->setGenerator(MongoIdGenerator::class);
    }

    public function schema(): array
    {
        return [
            'connection' => 'mongo',
            'table'      => 'with_complex_array',
        ];
    }

    public function buildFields($builder): void
    {
        $builder
            ->guid('oid')->alias('_id')->primary()
            ->arrayOf('addresses', 'Address')
        ;
    }
}

class EntityWithCustomCollation extends Model
{
    /**
     * @var string|ObjectId
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class EntityWithCustomCollationMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->setGenerator(MongoIdGenerator::class);
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'mongo',
            'table' => 'with_collation',
            'tableOptions' => [
                'collation' => [
                    'locale' => 'en',
                    'strength' => 2,
                ],
            ],
        ];
    }

    public function buildFields($builder): void
    {
        $builder
            ->string('id')->alias('_id')->primary()
            ->string('name')->unique()
        ;
    }
}
