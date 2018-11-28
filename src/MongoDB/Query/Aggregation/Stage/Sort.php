<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use Bdf\Prime\Query\CompilableClause;

/**
 * Class Sort
 *
 * @link https://docs.mongodb.com/manual/reference/operator/aggregation/sort/
 */
class Sort implements StageInterface
{
    /**
     * @var
     */
    private $fields = [];

    /**
     * {@inheritdoc}
     */
    public function operator()
    {
        return '$sort';
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(CompilableClause $clause, MongoGrammar $grammar)
    {
        return $grammar->sort($clause, $this->fields);
    }

    /**
     * @param string $field
     *
     * @return $this
     */
    public function asc($field)
    {
        return $this->add($field, 'ASC');
    }

    /**
     * @param string $field
     *
     * @return $this
     */
    public function desc($field)
    {
        return $this->add($field, 'DESC');
    }

    /**
     * @param string $field
     * @param string $order
     *
     * @return $this
     */
    public function add($field, $order)
    {
        $this->fields[] = [
            'sort'  => $field,
            'order' => $order,
        ];

        return $this;
    }

    /**
     * @param array|string $fields
     * @param string $order
     *
     * @return self
     */
    public static function make($fields, $order = 'asc')
    {
        $sort = new static();

        if (!is_array($fields)) {
            $fields = [$fields => $order];
        }

        foreach ($fields as $field => $order) {
            if (is_int($field)) {
                $field = $order;
                $order = 'asc';
            }

            $sort->$order($field);
        }

        return $sort;
    }
}
