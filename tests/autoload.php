<?php

if (!isset($_ENV['MONGO_HOST'])) {
    $_ENV['MONGO_HOST'] = getenv('MONGO_HOST') ?: '127.0.0.1';
}

date_default_timezone_set('Europe/Paris');

require_once __DIR__.'/../vendor/autoload.php';
include_once __DIR__.'/MongoDB/_files/PrimeTestCase.php';
include_once __DIR__.'/MongoDB/_files/MongoAssertion.php';
include_once __DIR__.'/MongoDB/_files/mongo_entities.php';
include_once __DIR__.'/MongoDB/_files/mongo_documents.php';
include_once __DIR__.'/MongoDB/_files/AddressType.php';
