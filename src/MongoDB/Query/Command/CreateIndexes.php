<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * Builds one or more indexes on a collection
 */
class CreateIndexes extends AbstractCommand
{
    /**
     * @var string
     */
    private $collection;

    /**
     * @var array
     */
    private $indexes = [];

    /**
     * @var int
     */
    private $current;


    /**
     * CreateIndexes constructor.
     *
     * @param string $collection
     * @param array $indexes
     */
    public function __construct($collection, array $indexes = [])
    {
        $this->collection = $collection;
        $this->indexes = $indexes;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'createIndexes';
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return parent::document() + ['indexes' => $this->indexes];
    }

    /**
     * {@inheritdoc}
     */
    protected function argument()
    {
        return $this->collection;
    }

    /**
     * Add a new index
     *
     * <code>
     * $command = new CreateIndexes('collection');
     * $command->add('search', [
     *     'name' => 1,
     *     'date' => -1
     * ]);
     * </code>
     *
     * @param string $name The index name
     * @param array $keys The index keys
     * @param array $options The index options
     *
     * @return $this
     */
    public function add($name, array $keys, array $options = [])
    {
        $this->current = count($this->indexes);
        $this->indexes[] = [
            'key'  => $keys,
            'name' => $name,
        ] + $options;

        return $this;
    }

    /**
     * Builds the index in the background so the operation does not block other database activities.
     * Specify true to build in the background. The default value is false.
     *
     * @param bool $background
     *
     * @return $this
     */
    public function background($background = true)
    {
        $this->indexes[$this->current]['background'] = $background;
        return $this;
    }

    /**
     * Creates a unique index so that the collection will not accept insertion or update of documents where the index key value matches an existing value in the index.
     * Specify true to create a unique index. The default value is false
     *
     * /!\ The option is unavailable for hashed indexes
     *
     * @param bool $unique
     *
     * @return $this
     */
    public function unique($unique = true)
    {
        $this->indexes[$this->current]['unique'] = $unique;
        return $this;
    }

    /**
     * If specified, the index only references documents that match the filter expression
     *
     * @param array $partialFilterExpression
     *
     * @return $this
     *
     * @since mongodb 3.2
     */
    public function partialFilterExpression(array $partialFilterExpression)
    {
        $this->indexes[$this->current]['partialFilterExpression'] = $partialFilterExpression;
        return $this;
    }

    /**
     * If true, the index only references documents with the specified field.
     * These indexes use less space but behave differently in some situations (particularly sorts).
     * The default value is false
     *
     * @param bool $sparse
     *
     * @return $this
     */
    public function sparse($sparse = true)
    {
        $this->indexes[$this->current]['sparse'] = $sparse;
        return $this;
    }

    /**
     * Specifies a value, in seconds, as a TTL to control how long MongoDB retains documents in this collection
     * This applies only to TTL indexes
     *
     * @param int $expireAfterSeconds
     *
     * @return $this
     */
    public function expireAfterSeconds($expireAfterSeconds)
    {
        $this->indexes[$this->current]['expireAfterSeconds'] = $expireAfterSeconds;
        return $this;
    }

    /**
     * Allows users to configure the storage engine on a per-index basis when creating an index
     *
     * @param array $storageEngine
     *
     * @return $this
     */
    public function storageEngine(array $storageEngine)
    {
        $this->indexes[$this->current]['storageEngine'] = $storageEngine;
        return $this;
    }

    /**
     * For text indexes, a document that contains field and weight pairs.
     * The weight is an integer ranging from 1 to 99,999 and denotes the significance of the field relative to the other indexed fields in terms of the score.
     * You can specify weights for some or all the indexed fields
     *
     * @param array $weights
     *
     * @return $this
     */
    public function weights($weights)
    {
        $this->indexes[$this->current]['weights'] = $weights;
        return $this;
    }

    /**
     * For text indexes, the language that determines the list of stop words and the rules for the stemmer and tokenizer
     *
     * @param string $defaultLanguage
     *
     * @return $this
     */
    public function defaultLanguage($defaultLanguage)
    {
        $this->indexes[$this->current]['default_language'] = $defaultLanguage;
        return $this;
    }

    /**
     * For text indexes, the name of the field, in the collection’s documents, that contains the override language for the document.
     * The default value is language
     *
     * @param string $languageOverride
     *
     * @return $this
     */
    public function languageOverride($languageOverride)
    {
        $this->indexes[$this->current]['language_override'] = $languageOverride;
        return $this;
    }

    /**
     * For text indexes, the text index version number. Version can be either 1 or 2
     *
     * @param int $textIndexVersion
     *
     * @return $this
     */
    public function textIndexVersion($textIndexVersion)
    {
        $this->indexes[$this->current]['textIndexVersion'] = $textIndexVersion;
        return $this;
    }

    /**
     * For 2dsphere indexes, the 2dsphere index version number. Version can be either 1 or 2
     *
     * @param int $sphereIndexVersion
     *
     * @return $this
     */
    public function sphereIndexVersion($sphereIndexVersion)
    {
        $this->indexes[$this->current]['2dsphereIndexVersion'] = $sphereIndexVersion;
        return $this;
    }

    /**
     * For 2d indexes, the number of precision of the stored geohash value of the location data.
     * The bits value ranges from 1 to 32 inclusive. The default value is 26
     *
     * @param int $bits
     *
     * @return $this
     */
    public function bits($bits)
    {
        $this->indexes[$this->current]['bits'] = $bits;
        return $this;
    }

    /**
     * For 2d indexes, the lower inclusive boundary for the longitude and latitude values
     * The default value is -180.0
     *
     * @param float $min
     *
     * @return $this
     */
    public function min($min)
    {
        $this->indexes[$this->current]['min'] = $min;
        return $this;
    }

    /**
     * For 2d indexes, the upper inclusive boundary for the longitude and latitude values.
     * The default value is 180.0
     *
     * @param float $max
     *
     * @return $this
     */
    public function max($max)
    {
        $this->indexes[$this->current]['max'] = $max;
        return $this;
    }

    /**
     * For geoHaystack indexes, specify the number of units within which to group the location values;
     * i.e. group in the same bucket those location values that are within the specified number of units to each other.
     *
     * The value must be greater than 0
     *
     * @param float $bucketSize
     *
     * @return $this
     */
    public function bucketSize($bucketSize)
    {
        $this->indexes[$this->current]['bucketSize'] = $bucketSize;
        return $this;
    }

    /**
     * Specifies the collation for the index.
     * Collation allows users to specify language-specific rules for string comparison, such as rules for letter case and accent marks
     *
     * @param array $collation
     *
     * @return $this
     *
     * @since mongodb 3.4
     */
    public function collation(array $collation)
    {
        $this->indexes[$this->current]['collation'] = $collation;
        return $this;
    }
}
