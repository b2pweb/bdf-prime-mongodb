<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * Explicitly creates a collection or view
 *
 * @link https://docs.mongodb.com/v3.4/reference/command/create/#dbcmd.create
 */
class Create extends AbstractCommand
{
    use CollectionOptionsTrait;

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
     * Create constructor.
     *
     * @param string $collection
     * @param array $options
     */
    public function __construct($collection, array $options = [])
    {
        $this->collection = $collection;
        $this->options = $options;
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
        return parent::document() + $this->options;
    }

    /**
     * {@inheritdoc}
     */
    protected function argument()
    {
        return $this->collection;
    }
}
