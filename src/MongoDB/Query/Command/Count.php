<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * count command
 * Count documents matching with a query
 *
 * <code>
 * $command = new Count('users');
 * $command->query([
 *     'name' => [
 *         '$regex' => '^t'
 *     ]
 * ]);
 * $result = $connection->executeCommand($command);
 * echo $result->toArray()[0]->n; // print the number of users with name starting with "t"
 * </code>
 *
 * @link https://docs.mongodb.com/manual/reference/command/count/
 */
class Count extends AbstractCommand
{
    /**
     * @var string
     */
    private $collection;

    /**
     * @var array
     */
    private $fields = [];


    /**
     * Count constructor.
     *
     * @param string $collection The collection name
     * @param array $fields The command arguments
     */
    public function __construct($collection, array $fields = [])
    {
        $this->collection = $collection;
        $this->fields     = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'count';
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return parent::document() + $this->fields;
    }

    /**
     * A query that selects which documents to count in the collection or view
     *
     * @param array $query
     *
     * @return $this
     */
    public function query(array $query)
    {
        $this->fields['query'] = $query;

        return $this;
    }

    /**
     * The maximum number of matching documents to return
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->fields['limit'] = (int) $limit;

        return $this;
    }

    /**
     * The number of matching documents to skip before returning results
     *
     * @param int $skip
     *
     * @return $this
     */
    public function skip($skip)
    {
        $this->fields['skip'] = (int) $skip;

        return $this;
    }

    /**
     * The index to use. Specify either the index name as a string or the index specification document
     *
     * @param string|array $hint
     *
     * @return $this
     */
    public function hint($hint)
    {
        $this->fields['hint'] = $hint;

        return $this;
    }

    /**
     * Specifies the read concern. The option has the following syntax
     * Possible read concern levels are:
     *
     * - "local". This is the default read concern level.
     * - "majority". Available for replica sets that use WiredTiger storage engine.
     * - "linearizable". Available for read operations on the primary only.
     *
     * For "local" (default) or "majority" read concern level,
     * you can specify the afterClusterTime option to have the read operation return data that meets the level
     * requirement and the specified after cluster time requirement
     *
     * @param string|array $value
     *
     * @return $this
     */
    public function readConcern($value)
    {
        if (!is_array($value)) {
            $value = ['level' => $value];
        }

        $this->fields['readConcern'] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function argument()
    {
        return $this->collection;
    }
}
