<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * Retrieve information, i.e. the name and options, about the collections and views in a database.
 * Specifically, the command returns a document that contains information with which to create a cursor to the collection information
 *
 * @link https://docs.mongodb.com/manual/reference/command/listCollections/
 */
class ListCollections extends AbstractCommand
{
    /**
     * @var array
     */
    private $filter;


    /**
     * ListCollections constructor.
     *
     * @param array $filter
     */
    public function __construct(array $filter = [])
    {
        $this->filter = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'listCollections';
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        $document = parent::document();

        if ($this->filter) {
            $document['filter'] = $this->filter;
        }

        return $document;
    }

    /**
     * A query expression to filter the list of collections
     *
     * @param array $filter
     *
     * @return $this
     *
     * @see https://docs.mongodb.com/manual/reference/command/listCollections/#list-collection-output For list of fields
     */
    public function filter(array $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Find collection by name
     *
     * @param string $name
     *
     * @return $this
     */
    public function byName($name)
    {
        $this->filter['name'] = $name;
        return $this;
    }

    /**
     * Filter collection by type
     *
     * @param string $type "collection" or "view"
     *
     * @return $this
     */
    public function byType($type)
    {
        $this->filter['type'] = $type;
        return $this;
    }

    /**
     * Filter collection by options
     *
     * @param array $options
     *
     * @return $this
     */
    public function byOptions($options)
    {
        $this->filter['options'] = $options;
        return $this;
    }

    /**
     * Filter collection by info
     *
     * Lists the following fields related to the collection:
     *
     * - readOnly
     *     boolean. If true the data store is read only.
     * - uuid
     *     UUID. Once established, the collection UUID does not change.
     *     The collection UUID remains the same across replica set members and shards in a sharded cluster.
     *
     * @param array $info
     *
     * @return $this
     */
    public function byInfo($info)
    {
        $this->filter['info'] = $info;
        return $this;
    }

    /**
     * Filter collection by its _id index info
     *
     * @param array $idIndex
     *
     * @return $this
     */
    public function byIdIndex($idIndex)
    {
        $this->filter['idIndex'] = $idIndex;
        return $this;
    }
}
