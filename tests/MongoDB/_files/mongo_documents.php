<?php

namespace Bdf\Prime\MongoDB\TestDocument;

use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Document\Selector\DiscriminatorFieldDocumentSelector;
use Bdf\Prime\MongoDB\Document\Selector\DocumentSelectorInterface;
use Bdf\Prime\MongoDB\Schema\CollectionDefinitionBuilder;
use Bdf\Prime\MongoDB\Test\Address;
use MongoDB\BSON\ObjectId;

class DiscrimiatorDocument extends MongoDocument
{
    public string $_type = '';
    public ?int $value = null;

    /**
     * @param int|null $value
     */
    public function __construct(?int $value = null)
    {
        $this->value = $value;
    }
}

class FooDocument extends DiscrimiatorDocument
{
    public string $_type = 'foo';
    public ?string $foo = null;

    public function __construct(?int $value = null, ?string $foo = null)
    {
        parent::__construct($value);

        $this->foo = $foo;
    }
}

class BarDocument extends DiscrimiatorDocument
{
    public string $_type = 'bar';
    public ?int $bar = null;

    public function __construct(?int $value = null, ?int $bar = null)
    {
        parent::__construct($value);

        $this->bar = $bar;
    }
}

class DiscrimiatorDocumentMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'with_discriminator';
    }

    protected function createDocumentSelector(string $documentBaseClass): DocumentSelectorInterface
    {
        return new DiscriminatorFieldDocumentSelector($documentBaseClass, [
            'foo' => FooDocument::class,
            'bar' => BarDocument::class,
        ]);
    }
}

class DocumentWithoutBaseClass
{
    private ?ObjectId $_id = null;
    private ?string $value = null;

    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    public function id(): ?ObjectId
    {
        return $this->_id;
    }

    public function setId(?ObjectId $id): DocumentWithoutBaseClass
    {
        $this->_id = $id;
        return $this;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): DocumentWithoutBaseClass
    {
        $this->value = $value;
        return $this;
    }
}

class DocumentWithoutBaseClassMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'without_base_class';
    }
}

/**
 * Class Person
 */
class PersonDocument extends MongoDocument
{
    protected ?string $firstName = null;
    protected ?string $lastName = null;
    protected ?int $age = null;
    protected array $tags = [];

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
class PersonDocumentMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'person_test';
    }

    protected function buildDefinition(CollectionDefinitionBuilder $builder): void
    {
        $builder->addIndex('age_sort')->on('age');
        $builder->addIndex('search_name')->unique()->on(['firstName', 'lastName']);
    }
}

/**
 * Class Home
 */
class HomeDocument extends MongoDocument
{
    protected ?Address $address = null;
    protected ?PersonDocument $proprietary = null;

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
     * @return PersonDocument
     */
    public function proprietary()
    {
        return $this->proprietary;
    }

    /**
     * @param PersonDocument $proprietary
     *
     * @return $this
     */
    public function setProprietary(PersonDocument $proprietary)
    {
        $this->proprietary = $proprietary;

        return $this;
    }
}

/**
 * Class HomeMapper
 */
class HomeDocumentMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'home_test';
    }

    protected function buildDefinition(CollectionDefinitionBuilder $builder): void
    {
        $builder
            ->addIndex('search')
            ->on('address', ['type' => 'text'])
            ->on('city', ['type' => 'text'])
            ->option('weights', [
                'city' => 2,
                'address' => 1,
            ])
        ;
    }
}
