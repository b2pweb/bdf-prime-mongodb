# Prime MongoDB driver
[![build](https://github.com/b2pweb/bdf-prime-mongodb/actions/workflows/php.yml/badge.svg)](https://github.com/b2pweb/bdf-prime-mongodb/actions/workflows/php.yml)
[![codecov](https://codecov.io/github/b2pweb/bdf-prime-mongodb/branch/2.0/graph/badge.svg?token=VOFSPEWYKX)](https://codecov.io/github/b2pweb/bdf-prime-mongodb)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-prime-mongodb.svg)](https://packagist.org/packages/b2pweb/bdf-prime-mongodb)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-prime-mongodb.svg)](https://packagist.org/packages/b2pweb/bdf-prime-mongodb)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-prime-mongodb/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-prime-mongodb)

MongoDB driver for [Prime](https://github.com/b2pweb/bdf-prime)

## Installation

Install with composer :

```bash
composer require b2pweb/bdf-prime-mongodb
```

Create connection :

```php
<?php
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Mongo;

// declare your connexion manager
$connections = new ConnectionManager();

// Declare connection without username and password
$connections->declareConnection('mongo', 'mongodb://127.0.0.1/my_collection?noAuth=true');
// With credentials
$connections->declareConnection('mongo', 'mongodb://user:password@127.0.0.1/my_database');

// Get the connection locator
$locator = new MongoCollectionLocator($connections);
Mongo::configure($locator); // Configure active record system
```

## Usage

### Declare a document

Declare the base document class by extending `Bdf\Prime\MongoDB\Document\MongoDocument`. 
The `_id` field is declared by this class.

You can use typed property for generate an automatic type mapping.
Untyped fields will not be converted when retrieving from mongo.

> Note: it's advisable to declare all fields as nullable in case of missing field

```php
<?php

use Bdf\Prime\MongoDB\Document\MongoDocument;
use \MongoDB\BSON\Binary;

class MyDocument extends MongoDocument
{
    public ?string $name;
    public ?DateTimeInterface $creationDate;
    public ?Binary $data;
}
```

### Declare a Mapper

For a basic usage, simply declare a mapper by extending `Bdf\Prime\MongoDB\Document\DocumentMapper`, and implementing `connection()` and `collection()` methods :

```php
<?php

use Bdf\Prime\MongoDB\Document\DocumentMapper;

class MyDocumentMapper extends DocumentMapper
{
    /**
     * {@inheritdoc}
     */
    public function connection(): string
    {
        // The declared connection name 
        return 'mongo';
    }

    /**
     * {@inheritdoc}
     */
    public function collection(): string
    {
        return 'my_collection'; // The storage collection name
    }
}
```

Mapping and fields will be automatically resolved from the document class.

### Querying MongoDB

The query system use Prime interfaces, so usage is almost the same :

```php
<?php
// Get the query
/** @var \Bdf\Prime\MongoDB\Query\MongoQuery $query */
$query = MyDocument::query();

$query
    ->where('name', 'John') // Simple where works as expected
    ->where('value.attr', ':like', 'P%') // "like" operator is converted to a regex
    ->where('value.foo', '$type', 'javascript') // Use mongodb operator
;

// Get all documents which match with filters
$query->all();

// First returns the first matching document or null
$query->first();
```

### Testing

Use `Bdf\Prime\MongoDB\Test\MongoTester` for create testing data.

```php
<?php

use PHPUnit\Framework\TestCase;
use Bdf\Prime\MongoDB\Test\MongoTester;

class MyTest extends TestCase
{
    private MongoTester $tester;

    protected function setUp() : void
    {
        $this->tester = new MongoTester();
        $this->tester
            // Declare given collections. Collections are automatically declared when `push()` on new collection
            ->declare(FooDocument::class, BarDocument::class)
            // Push to mongo given documents with a key for retrieve the value on test
            ->push([
                'doc1' => new MyDocument(),
                'doc2' => new MyDocument(),
            ])
        ;
    }
    
    protected function tearDown() : void
    {
        $this->tester->destroy(); // Drop all declared collections
    }
    
    public function my_test()
    {
        $doc1 = $this->tester->get('doc1'); // get document declared on setUp method
        $this->tester->push($newDoc = new FooDocument()); // Push a single document without a key (cannot be retrieved with `get()`)
        
        // ...
        
        $this->assertNotEquals($doc1, $this->tester->refresh($doc1)); // Retrieve the DB version of the given document
        $this->assertNull($newDoc, $this->tester->refresh($newDoc)); // refresh can be used to check if the document exists on DB
    }
    
    public function with_array_access_test()
    {
        // Array access syntax can also be used instead of "classic" method calls
        $doc1 = $this->tester['doc1']; // get document declared on setUp method
        $this->tester[] = $newDoc = new FooDocument(); // Push a single document without a key (cannot be retrieved with `get()`)
        $this->tester['doc3'] = new MyDocument(); // Push a single document with a key

        // ...

        $this->assertNotEquals($doc1, $this->tester[$doc1]); // Retrieve the DB version of the given document
        $this->assertTrue(isset($this->tester[$newDoc])); // Check if the document exists on DB
        
        unset($this->tester['doc3']); // Deleted a declared document
        unset($this->tester[$newDoc]); // Can also be used to delete a document without key

        $this->assertFalse(isset($this->tester[$newDoc])); // Document is now deleted
    }
}
```

### Case-insensitive search and index

To enable case-insensitive search by default, you can add default collation on table options.
See [Case Insensitive Indexes](https://docs.mongodb.com/manual/core/index-case-insensitive/#case-insensitive-indexes-on-collections-with-a-default-collation)

```php
<?php

use Bdf\Prime\MongoDB\Document\DocumentMapper;

class MyDocumentMapper extends DocumentMapper
{
    /**
     * {@inheritdoc}
     */
    public function connection(): string
    {
        // The declared connection name 
        return 'mongo';
    }

    /**
     * {@inheritdoc}
     */
    public function collection(): string
    {
        return 'my_collection'; // The storage collection name
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildDefinition(\Bdf\Prime\MongoDB\Schema\CollectionDefinitionBuilder $builder) : void
    {
        $builder->collation(['locale' => 'en', 'strength' => 2]);
    }
}
```

### Multiple document classes

Mongo is schemaless, so a collection can store documents with different formats.
You can select a document class corresponding to DB fields by using a custom `Bdf\Prime\MongoDB\Document\Selector\DocumentSelectorInterface`,
declared using `DocumentMapper::createDocumentSelector()` :

```php
<?php

// Declare document classes. Note: all documents classes must inherit from a base class 
class BaseDocument extends MongoDocument
{
    public ?string $_type = null; // _type is the default field used by DiscriminatorFieldDocumentSelector
}

class FooDocument extends BaseDocument
{
    public ?string $_type = 'foo';
    public ?string $foo = null;
}

class BarDocument extends BaseDocument
{
    public ?string $_type = 'bar';
    public ?string $bar = null;
}

// Declare a single mapper
class MyDocumentMapper extends DocumentMapper
{
    /**
     * {@inheritdoc}
     */
    public function connection(): string
    { 
        return 'mongo';
    }

    /**
     * {@inheritdoc}
     */
    public function collection(): string
    {
        return 'my_collection';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function createDocumentSelector(string $documentBaseClass): DocumentSelectorInterface
    {
        // Define document class mapping
        return new DiscriminatorFieldDocumentSelector($documentBaseClass, [
            'foo' => FooDocument::class,
            'bar' => BarDocument::class,
        ]);
        
        // If you can't introduce a field for perform discrimination, you can check fields existence :
        return new DiscriminatorFieldDocumentSelector($documentBaseClass, [
            FooDocument::class => ['foo'],
            BarDocument::class => ['bar'],
        ]);
    }
}

// Get the base collection : it handles all document types
$collection = BaseDocument::collection();

$collection->add(new BaseDocument(...));
$collection->add(new FooDocument(...));
$collection->add(new BarDocument(...));

$collection->all(); // Return all documents from all types

// Handle only "FooDocument" document class
$fooCollection = FooDocument::collection();
$fooCollection->all(); // Return only document of type "foo"
```
