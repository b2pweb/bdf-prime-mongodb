<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\MongoDB\Query\Aggregation\Compiler\PipelineCompilerInterface;
use Bdf\Prime\MongoDB\Query\Aggregation\PipelineInterface;

/**
 * Perform a group aggregation
 *
 * @link https://docs.mongodb.com/manual/reference/operator/aggregation/group/
 */
class Group implements StageInterface
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var array
     */
    protected $fields = [];


    /**
     * Group constructor.
     *
     * @param mixed $id The group expression. NULL to perform operations on all rows
     */
    public function __construct($id)
    {
        $this->by($id);
    }

    /**
     * {@inheritdoc}
     */
    public function operator()
    {
        return '$group';
    }

    /**
     * {@inheritdoc}
     */
    public function compile(PipelineCompilerInterface $compiler)
    {
        return $compiler->compileGroup($this->export());
    }

    /**
     * Change the group expression
     *
     * @param mixed $expression
     *
     * @return $this
     */
    public function by($expression)
    {
        if ($expression === null) {
            $this->id = null;
        } elseif (is_string($expression)) {
            $this->id = $expression;
        } else {
            $this->id = Project::make($expression)->export();
        }

        return $this;
    }

    /**
     * Add accumulator operator for a group field
     *
     * @param string $field
     * @param string $operator
     * @param mixed $expression
     *
     * @return $this
     */
    public function accumulator($field, $operator, $expression)
    {
        // If expression is a string, it represents (only on $group context) a field name
        if (is_string($expression) && $expression{0} !== '$') {
            $expression = '$'.$expression;
        }

        $this->fields[$field] = [
            $operator => $expression
        ];

        return $this;
    }

    /**
     * Returns a sum of numerical values. Ignores non-numeric values.
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function sum($field, $expression)
    {
        return $this->accumulator($field, '$sum', $expression);
    }

    /**
     * Returns an average of numerical values. Ignores non-numeric values
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function avg($field, $expression)
    {
        return $this->accumulator($field, '$avg', $expression);
    }

    /**
     * Returns a value from the first document for each group.
     * Order is only defined if the documents are in a defined order.
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function first($field, $expression)
    {
        return $this->accumulator($field, '$first', $expression);
    }

    /**
     * Returns a value from the last document for each group.
     * Order is only defined if the documents are in a defined order.
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function last($field, $expression)
    {
        return $this->accumulator($field, '$last', $expression);
    }

    /**
     * Returns the highest expression value for each group
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function max($field, $expression)
    {
        return $this->accumulator($field, '$max', $expression);
    }

    /**
     * Returns the lowest expression value for each group
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function min($field, $expression)
    {
        return $this->accumulator($field, '$min', $expression);
    }

    /**
     * Returns an array of expression values for each group
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function push($field, $expression)
    {
        return $this->accumulator($field, '$push', $expression);
    }

    /**
     * Returns an array of unique expression values for each group.
     * Order of the array elements is undefined.
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function addToSet($field, $expression)
    {
        return $this->accumulator($field, '$addToSet', $expression);
    }

    /**
     * Returns the population standard deviation of the input values
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function stdDevPop($field, $expression)
    {
        return $this->accumulator($field, '$stdDevPop', $expression);
    }

    /**
     * Returns the sample standard deviation of the input values
     *
     * @param string $field
     * @param mixed $expression
     *
     * @return $this
     */
    public function stdDevSamp($field, $expression)
    {
        return $this->accumulator($field, '$stdDevSamp', $expression);
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return ['_id' => $this->id] + $this->fields;
    }

    /**
     * Make Group instance
     *
     * @param mixed $expression
     * @param mixed $operations
     *
     * @return Group
     *
     * @see PipelineInterface::group()
     */
    public static function make($expression, $operations)
    {
        $group = new Group($expression);

        if ($operations === null) {
            return $group;
        }

        if (is_array($operations)) {
            foreach ($operations as $field => $def) {
                foreach ($def as $fn => $expr) {
                    $group->$fn($field, $expr);
                }
            }
        } elseif ($operations instanceof \Closure) {
            $operations($group);
        }

        return $group;
    }
}
