# Prime Indexer
[![build](https://github.com/b2pweb/bdf-prime-mongodb/actions/workflows/php.yml/badge.svg)](https://github.com/b2pweb/bdf-prime-mongodb/actions/workflows/php.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-mongodb/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-mongodb/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-mongodb/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-mongodb/?branch=master)
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

// declare your connexion manager
$connexions = new ConnectionManager();

// Declare connection without username and password
$connexions->declareConnection('mongo', 'mongodb://127.0.0.1/my_collection?noAuth=true');
// With credentials
$connexions->declareConnection('mongo', 'mongodb://user:password@127.0.0.1/my_database');

```

## Usage

### Declare a Mapper

MongoDB mapper declaration is almost same as SQL mapper, only primary key declaration differ :

```php
<?php

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\MongoDB\Odm\MongoIdGenerator;

class MyDocumentMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        // Declare generator for generation MongoId on insertion 
        $this->setGenerator(MongoIdGenerator::class);
    }

    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'mongo',
            'table' => 'my_collection', // Use 'table' for define the collection name
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            // The mongo id must be declared as primary
            ->string('oid')->primary()
            
            // The rest of fields declaration is same as other prime mappers
            ->string('name')
            ->object('value')
            
            // Unlike SQL, there is no need to define an alias for embedded values
            // By default (i.e. without alias), the embedded value will be stored as embedded document on the collection
            ->embedded('foo', function ($builder) {
                $builder
                    ->string('bar')
                    ->string('rab')
                ;
            })
        ;
    }
}
```

### Querying MongoDB

The query system use Prime interfaces, so usage is almost the same :

```php
<?php
// Get the query
/** @var \Bdf\Prime\MongoDB\Query\MongoQuery $query */
$query = MyDocument::builder();

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

The `TestPack` of Prime is compatible with MongoDB
