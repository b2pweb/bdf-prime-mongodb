<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * Explicitly creates a collection or view
 *
 * @link https://docs.mongodb.com/v3.4/reference/command/create/#dbcmd.create
 */
class Create extends AbstractCommand
{
    /** No validation for inserts or updates. */
    public const VALIDATION_LEVEL_OFF = 'off';
    /** Default. Apply validation rules to all inserts and all updates. */
    public const VALIDATION_LEVEL_STRICT = 'strict';
    /** Apply validation rules to inserts and to updates on existing valid documents. Do not apply rules to updates on existing invalid documents. */
    public const VALIDATION_LEVEL_MODERATE = 'moderate';

    /** Default. Documents must pass validation before the write occurs. Otherwise, the write operation fails */
    public const VALIDATION_ACTION_ERROR = 'error';
    /** Documents do not have to pass validation. If the document fails validation, the write operation logs the validation failure. */
    public const VALIDATION_ACTION_WARN  = 'warn';

    /**
     * @var string
     */
    private $collection;

    /**
     * @var array
     */
    private $fields = [];


    /**
     * Create constructor.
     *
     * @param string $collection
     * @param array $fields
     */
    public function __construct($collection, array $fields = [])
    {
        $this->collection = $collection;
        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'create';
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
     * To create a capped collection, specify true.
     * If you specify true, you must also set a maximum size in the size field.
     *
     * @param bool $capped
     *
     * @return $this
     */
    public function capped($capped = true)
    {
        $this->fields['capped'] = $capped;
        return $this;
    }

    /**
     * Specify false to disable the automatic creation of an index on the _id field.
     *
     * @param bool $autoIndexId
     *
     * @return $this
     *
     * @deprecated since mongodb 3.2
     */
    public function autoIndexId($autoIndexId = true)
    {
        $this->fields['autoIndexId'] = $autoIndexId;
        return $this;
    }

    /**
     * Specify a maximum size in bytes for a capped collection.
     * Once a capped collection reaches its maximum size, MongoDB removes the older documents to make space for the new documents.
     * The size field is required for capped collections and ignored for other collections.
     *
     * @param int $size
     *
     * @return $this
     */
    public function size($size)
    {
        $this->fields['size'] = $size;
        return $this;
    }

    /**
     * The maximum number of documents allowed in the capped collection.
     * The size limit takes precedence over this limit.
     * If a capped collection reaches the size limit before it reaches the maximum number of documents,
     * MongoDB removes old documents.
     * If you prefer to use the max limit, ensure that the size limit,
     * which is required for a capped collection, is sufficient to contain the maximum number of documents.
     *
     * @param integer $max
     *
     * @return $this
     */
    public function max($max)
    {
        $this->fields['max'] = $max;
        return $this;
    }

    /**
     * Allows users to specify configuration to the storage engine on a per-collection basis when creating a collection.
     * The value of the storageEngine option should take the following form:
     *
     * [ <storage-engine-name> => <options> ]
     *
     * @param array $storageEngine
     *
     * @return $this
     *
     * @since mongodb 3.0
     */
    public function storageEngine(array $storageEngine)
    {
        $this->fields['storageEngine'] = $storageEngine;
        return $this;
    }

    /**
     * Allows users to specify validation rules or expressions for the collection
     * The validator option takes a document that specifies the validation rules or expressions.
     * You can specify the expressions using the same operators as the query operators
     * with the exception of $geoNear, $near, $nearSphere, $text, and $where.
     *
     * @param array $validator
     *
     * @return $this
     *
     * @since mongodb 3.2
     */
    public function validator(array $validator)
    {
        $this->fields['validator'] = $validator;
        return $this;
    }

    /**
     * Determines how strictly MongoDB applies the validation rules to existing documents during an update
     *
     * @param string $validationLevel
     *
     * @return $this
     *
     * @since mongodb 3.2
     */
    public function validationLevel($validationLevel)
    {
        $this->fields['validationLevel'] = $validationLevel;
        return $this;
    }

    /**
     * Determines whether to error on invalid documents or just warn about the violations but allow invalid documents to be inserted
     *
     * @param string $validationAction
     *
     * @return $this
     *
     * @since mongodb 3.2
     */
    public function validationAction($validationAction)
    {
        $this->fields['validationAction'] = $validationAction;
        return $this;
    }

    /**
     * Allows users to specify a default configuration for indexes when creating a collection
     * The indexOptionDefaults option accepts a storageEngine
     *
     * @param array $indexOptionDefaults
     *
     * @return $this
     *
     * @since mongodb 3.2
     */
    public function indexOptionDefaults(array $indexOptionDefaults)
    {
        $this->fields['indexOptionDefaults'] = $indexOptionDefaults;
        return $this;
    }

    /**
     * The name of the source collection or view from which to create the view.
     * The name is not the full namespace of the collection or view;
     * i.e. does not include the database name and implies the same database as the view to create.
     *
     * @param string $viewOn
     *
     * @return $this
     *
     * @since mongodb 3.4
     */
    public function viewOn($viewOn)
    {
        $this->fields['viewOn'] = $viewOn;
        return $this;
    }

    /**
     * An array that consists of the aggregation pipeline stage.
     * create creates the view by applying the specified pipeline to the viewOn collection or view.
     *
     * The view definition is public; i.e. db.getCollectionInfos() and explain operations on the view will include the pipeline that defines the view.
     * As such, avoid referring directly to sensitive fields and values in view definitions.
     *
     * @param array $pipeline
     *
     * @return $this
     *
     * @since mongodb 3.4
     */
    public function pipeline(array $pipeline)
    {
        $this->fields['pipeline'] = $pipeline;
        return $this;
    }

    /**
     * Specifies the default collation for the collection or the view.
     * Collation allows users to specify language-specific rules for string comparison, such as rules for lettercase and accent marks.
     *
     * @param array $collation
     *
     * @return $this
     *
     * @since mongodb 3.4
     */
    public function collation(array $collation)
    {
        $this->fields['collation'] = $collation;
        return $this;
    }
}
