<?php

namespace Bdf\Prime\MongoDB\Query\Command;

use MongoDB\Driver\Command;

/**
 * Wrap array into command
 */
class ArrayCommand implements CommandInterface
{
    /**
     * @var array
     */
    private $document;


    /**
     * ArrayCommand constructor.
     *
     * @param array $document
     */
    public function __construct(array $document)
    {
        $this->document = $document;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        reset($this->document);
        return key($this->document);
    }

    /**
     * {@inheritdoc}
     */
    public function document()
    {
        return $this->document;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return new Command($this->document);
    }
}
