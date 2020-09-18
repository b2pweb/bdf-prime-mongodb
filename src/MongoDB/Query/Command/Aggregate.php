<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * Aggregate command
 * Performs aggregation operation using the aggregation pipeline.
 * The pipeline allows users to process data from a collection or other source with a sequence of stage-based manipulations.
 *
 * @link https://docs.mongodb.com/manual/reference/command/aggregate/
 */
class Aggregate extends AbstractCommand
{
    /**
     * @var string
     */
    private $collection;

    /**
     * @var array
     */
    private $fields = [
        'pipeline' => []
    ];


    /**
     * Aggregate constructor.
     *
     * @param string $collection
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'aggregate';
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return parent::document() + $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function argument()
    {
        return $this->collection;
    }

    /**
     * An array of aggregation pipeline stages that process and transform the document stream as part of the aggregation pipeline.
     *
     * @param array $pipelines
     *
     * @return $this
     */
    public function pipeline(array $pipelines)
    {
        $this->fields['pipeline'] = $pipelines;

        return $this;
    }

    /**
     * @param array $pipeline
     *
     * @return $this
     */
    public function add(array $pipeline)
    {
        $this->fields['pipeline'][] = $pipeline;

        return $this;
    }

    /**
     * Specifies to return the information on the processing of the pipeline.
     *
     * @param bool $explain
     *
     * @return $this
     */
    public function explain($explain = true)
    {
        $this->fields['explain'] = $explain;

        return $this;
    }

    /**
     * Enables writing to temporary files. When set to true, aggregation stages can write data to the _tmp subdirectory in the dbPath directory.
     *
     * @param bool $allowDiskUse
     *
     * @return $this
     */
    public function allowDiskUse($allowDiskUse = true)
    {
        $this->fields['allowDiskUse'] = $allowDiskUse;

        return $this;
    }

    /**
     * An array of aggregation pipeline stages that process and transform the document stream as part of the aggregation pipeline.
     *
     * @param array $cursor
     *
     * @return $this
     */
    public function cursor(array $cursor)
    {
        $this->fields['cursor'] = (object) $cursor;

        return $this;
    }

    /**
     * Specifies a time limit in milliseconds for processing operations on a cursor.
     * If you do not specify a value for maxTimeMS, operations will not time out.
     * A value of 0 explicitly specifies the default unbounded behavior.
     *
     * @param int $maxTimeMS
     *
     * @return $this
     */
    public function maxTimeMS($maxTimeMS)
    {
        $this->fields['maxTimeMS'] = $maxTimeMS;

        return $this;
    }

    /**
     * Available only if you specify the $out aggregation operator.
     * Enables aggregate to bypass document validation during the operation.
     * This lets you insert documents that do not meet the validation requirements.
     *
     * @param bool $bypassDocumentValidation
     *
     * @return $this
     */
    public function bypassDocumentValidation($bypassDocumentValidation = true)
    {
        $this->fields['bypassDocumentValidation'] = $bypassDocumentValidation;

        return $this;
    }
}
