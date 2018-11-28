<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\Prime\MongoDB\Query\Aggregation\PipelineInterface;
use Bdf\Prime\MongoDB\Query\Compiler\MongoGrammar;
use Bdf\Prime\Query\CompilableClause;

/**
 * Aggregation projection
 * Passes along the documents with the requested fields to the next stage in the pipeline.
 * The specified fields can be existing fields from the input documents or newly computed fields.
 *
 * @link https://docs.mongodb.com/manual/reference/operator/aggregation/project/
 */
class Project implements StageInterface
{
    /**
     * @var array
     */
    private $expression = [];


    /**
     * Project constructor.
     *
     * @param array $expression
     */
    public function __construct(array $expression = [])
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function operator()
    {
        return '$project';
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this->expression;
    }


    /**
     * {@inheritdoc}
     */
    public function compile(CompilableClause $clause, MongoGrammar $grammar)
    {
        return $grammar->projection($clause, $this->expression);
    }

    /**
     * Add a new field to the projection
     *
     * <code>
     * $pipeline->project(function (Project $project) {
     *     $project
     *         ->add('my.embedded.field', 'embedded')
     *         ->add('attr')
     *     ;
     * }); // Will result in : ['embedded' => '$my.embedded.field', 'attr' => 1]
     * </code>
     *
     * @param string $field The field name
     * @param string|null $alias The field alias (or null to keep original field name)
     *
     * @return $this
     */
    public function add($field, $alias = null)
    {
        $this->expression[] = [
            'column' => $field,
            'alias'  => $alias
        ];

        return $this;
    }

    /**
     * Add a new evaluated value on the projection
     *
     * <code>
     * $project->evaluate('diffTime', '$subtract', ['$end', '$start']);
     * $project->evaluate('diffTime', ['$subtract' => ['$end', '$start']]);
     * </code>
     *
     * @param string $field The new field name
     * @param string|array $operator The operator, or the expression if last parameter is not provided
     * @param mixed $expression <p>
     * The operand. Can be :
     * - A scalar value
     * - A field name or variable starting with $ sign (ex: '$field')
     * - An array of fields (ex: ['$field1', '$field2']
     * - A complex expression array (ex: ['$ceil' => ['$sum' => ['$max' => ['$field1', '$field2']], '$other']])
     * </p>
     *
     * @return $this
     */
    public function evaluate($field, $operator, $expression = null)
    {
        $this->expression[] = [
            'column'     => $field,
            'expression' => is_array($operator) ? $operator : [$operator => $expression],
        ];

        return $this;
    }

    /**
     * Make project instance
     *
     * @param array|\Closure $fields
     *
     * @return Project
     *
     * @see PipelineInterface::project()
     */
    public static function make($fields)
    {
        $project = new Project();

        if (is_array($fields)) {
            foreach ($fields as $alias => $column) {
                if (is_array($column)) {
                    $project->evaluate($alias, $column);
                } else {
                    $project->add($column, is_int($alias) ? null : $alias);
                }
            }
        } elseif ($fields instanceof \Closure) {
            $fields($project);
        } elseif (is_string($fields)) {
            $project->add($fields);
        }

        return $project;
    }
}
