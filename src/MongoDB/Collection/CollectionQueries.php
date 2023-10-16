<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\MongoDB\Document\DocumentMapperInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Query\MongoKeyValueQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\Query\ClauseInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\ReadCommandInterface;

use function method_exists;

/**
 * Class CollectionQueries
 *
 * @template D as object
 */
class CollectionQueries
{
    /**
     * @var MongoCollectionInterface<D>
     */
    private MongoCollectionInterface $collection;

    /**
     * @var DocumentMapperInterface<D>
     */
    private DocumentMapperInterface $mapper;

    /**
     * @var MongoConnection
     */
    private MongoConnection $connection;

    /**
     * @var CollectionPreprocessor|null
     */
    private ?CollectionPreprocessor $preprocessor = null;

    /**
     * @var CollectionQueryExtension<D>|null
     */
    private ?CollectionQueryExtension $extension = null;

    /**
     * @var array<string, callable>
     */
    private array $queries;

    /**
     * @var array<string, callable>
     */
    private array $filters;

    /**
     * @param MongoCollectionInterface<D> $collection
     * @param DocumentMapperInterface<D> $mapper
     * @param MongoConnection $connection
     */
    public function __construct(MongoCollectionInterface $collection, DocumentMapperInterface $mapper, MongoConnection $connection)
    {
        $this->collection = $collection;
        $this->mapper = $mapper;
        $this->connection = $connection;
        $this->queries = $mapper->queries();
        $this->filters = method_exists($mapper, 'filters') ? $mapper->filters() : [];
    }

    /**
     * Create a query builder for perform search on the collection
     *
     * @return MongoQuery<D>
     */
    public function query(): MongoQuery
    {
        /** @var MongoQuery<D> */
        return $this->make(MongoQuery::class);
    }

    /**
     * Create a query for perform simple key / value search on the current repository
     *
     * /!\ Key value query can perform only equality comparison
     *
     * <code>
     * // Search by name
     * $queries->keyValue('name', 'myName')->first();
     *
     * // Get an empty key value query
     * $queries->keyValue()->where(...);
     *
     * // With criteria
     * $queries->keyValue(['name' => 'John', 'customer.id' => 5])->all();
     * </code>
     *
     * @param string|array|null $attribute The search attribute, or criteria
     * @param mixed $value The search value
     *
     * @return MongoKeyValueQuery<D>|null The query, or null if not supported
     */
    public function keyValue($attribute = null, $value = null): ?MongoKeyValueQuery
    {
        // Constraints add raw filters, which is not supported by KeyValueQuery
        if (!empty($this->mapper->constraints())) {
            return null;
        }

        /** @var MongoKeyValueQuery<D> $query */
        $query = $this->make(MongoKeyValueQuery::class);

        if ($attribute) {
            $query->where($attribute, $value);
        }

        return $query;
    }

    /**
     * Make a query
     *
     * @param class-string<Q> $query The query name or class name to make
     *
     * @return Q
     *
     * @template Q as object
     */
    public function make(string $query): CommandInterface
    {
        if (!$preprocessor = $this->preprocessor) {
            $this->preprocessor = $preprocessor = new CollectionPreprocessor($this->collection);
        }

        if (!$extension = $this->extension) {
            $extension = $this->extension = new CollectionQueryExtension($this->collection, $this->mapper);
        }

        $query = $this->connection
            ->make($query, $preprocessor)
            ->from($this->mapper->collection())
        ;

        if ($query instanceof ReadCommandInterface) {
            $extension->apply($query);
        }

        if ($query instanceof ClauseInterface) {
            $query->setCustomFilters($this->filters);
        }

        return $query;
    }

    /**
     * Delegates call to corresponding query
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (isset($this->queries[$name])) {
            return $this->queries[$name]($this->collection, ...$arguments);
        }

        return $this->query()->$name(...$arguments);
    }
}
