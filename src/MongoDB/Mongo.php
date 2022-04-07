<?php

namespace Bdf\Prime\MongoDB;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use RuntimeException;

/**
 * Facade for prime mongodb
 */
final class Mongo
{
    /**
     * @var callable():MongoCollectionLocator|MongoCollectionLocator|null
     */
    private static $locator;

    /**
     * @param callable():MongoCollectionLocator|MongoCollectionLocator
     * @return void
     * @todo config as array of connections ?
     * @todo use Locatorizable by default ?
     */
    public static function configure($locator): void
    {
        self::$locator = $locator;
    }

    public static function isConfigured(): bool
    {
        return self::$locator !== null;
    }

    public static function locator(): MongoCollectionLocator
    {
        if (self::$locator === null) {
            throw new RuntimeException('Prime MongoDB is not configured. Call Mongo::configure() before.');
        }

        if (is_callable(self::$locator)) {
            self::$locator = (self::$locator)();
        }

        return self::$locator;
    }

    /**
     * @param class-string<D> $documentClass
     * @return MongoCollection<D>
     *
     * @template D as object
     */
    public static function collection(string $documentClass): MongoCollection
    {
        return self::locator()->collection($documentClass);
    }
}
