<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation;

/**
 * Interface for handle aggregation pipeline
 *
 * All operations of the pipeline will be pushed at the end of the aggregation
 */
interface PipelineInterface
{
    /**
     * Group query for perform aggregation
     *
     * <code>
     * $pipeline->group('time'); // group all documents by field time
     * $pipeline->group(null, ['avgTime' => ['avg' => 'time']]); // Get average time on all documents
     * $pipeline->group([
     *     'time' => [
     *         '$ceil' => ['$subtract' => ['$field1', '$field2']]
     *     ]
     * ], ['total' => ['$sum' => 1]]); // Count all documents with same time difference
     * $pipeline->null, function (Group $group) {
     *     $group->sum('total', '$field');
     * }); // Use Closure to build the group
     * </code>
     *
     * @param mixed $expression <p>
     * The group expression.
     * - Can be NULL to calculate on all documents
     * - A string for group by field
     * - An array or Closure to perform group on complex expression. The format will be the same as PipelineInterface::project()
     * </p>
     *
     * @param null|array|\Closure $operations <p>
     * The aggregation operations.
     * Can be :
     * - NULL for only group documents
     * - A closure with the group builder
     * - An array of operations
     * </p>
     *
     * @return $this
     */
    public function group($expression = null, $operations = null);

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * <code>
     *     $pipeline
     *         ->select('u.name')
     *         ->match(['u.id' => '1']);
     *
     *     // You can build nested expressions
     *    $query
     *         ->match(function($query) {
     *             $query->where(['id :like' => '123%']);
     *         })
     * </code>
     *
     * @param  string|array $column The restriction predicates.
     * @param  string $operator
     * @param  mixed $value
     *
     * @return $this This Query instance.
     */
    public function match($column, $operator = null, $value = null);

    /**
     * Project fields
     * Passes along the documents with the requested fields to the next stage in the pipeline.
     * The specified fields can be existing fields from the input documents or newly computed fields.
     *
     * <code>
     * $pipeline->project([
     *     'field',
     *     'my_alias' => 'field',
     *     'evaluated' => ['$operator' => '$expression']
     * ]);
     * </code>
     *
     * @param array|\Closure $fields
     *
     * @return $this
     */
    public function project($fields);

    /**
     * Sorts all input documents and returns them to the pipeline in sorted order.
     *
     * @param array|string $fields
     * @param string|null $order
     *
     * @return $this
     */
    public function sort($fields, $order = null);

    /**
     * Limits the number of documents passed to the next stage in the pipeline.
     *
     * @param integer $limit A positive integer
     *
     * @return $this
     */
    public function limit($limit);
}
