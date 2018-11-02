<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\MongoDB\Driver\MongoConnection;

/**
 * Base type for auto execute query, command or write operations
 */
interface SelfExecutable
{
    /**
     * Auto-execute the query on the connection
     *
     * <code>
     * $query = $connection->builder();
     * $query->...; // Build and compile the query
     * $compiled = $query->compile();
     *
     * $compiled->execute($connection); // Execute the Query
     * </code>
     *
     * @param MongoConnection $connection
     *
     * @return ResultSetInterface
     */
    public function execute(MongoConnection $connection);
}
