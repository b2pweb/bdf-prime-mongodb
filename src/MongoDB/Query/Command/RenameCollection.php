<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * Changes the name of an existing collection.
 * Specify collection names to renameCollection in the form of a complete namespace (<database>.<collection>)
 */
class RenameCollection extends AbstractCommand
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @var bool
     */
    private $dropTarget = false;


    /**
     * RenameCollection constructor.
     *
     * @param string $from
     * @param string $to
     */
    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'renameCollection';
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return parent::document() + [
            'to' => $this->to,
            'dropTarget' => $this->dropTarget
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function argument()
    {
        return $this->from;
    }

    /**
     * The new namespace of the collection.
     * If the new namespace specifies a different database,
     * the renameCollection command copies the collection to the new database and drops the source collection.
     *
     * @param string $to
     *
     * @return $this
     */
    public function to($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * If true, mongod will drop the target of renameCollection prior to renaming the collection
     * The default value is false
     *
     * @param bool $dropTarget
     *
     * @return $this
     */
    public function dropTarget($dropTarget = true)
    {
        $this->dropTarget = $dropTarget;
        return $this;
    }
}
