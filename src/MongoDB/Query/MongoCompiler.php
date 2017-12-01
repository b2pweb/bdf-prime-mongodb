<?php

namespace Bdf\Prime\MongoDB\Query;

use Bdf\Prime\Query\Clause;
use Bdf\Prime\Query\Compiler\AbstractCompiler;
use Bdf\Prime\Query\Expression\ExpressionTransformerInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;

/**
 * MongoCompiler
 */
class MongoCompiler extends AbstractCompiler
{
    /**
     * @var array
     */
    static protected $operatorsMap = [
        '<'   => '$lt',
        ':lt' => '$lt',

        '<='   => '$lte',
        ':lte' => '$lte',

        '>'   => '$gt',
        ':gt' => '$gt',

        '>='   => '$gte',
        ':gte' => '$gte',

        '~='     => '$regex',
        '=~'     => '$regex',
        ':regex' => '$regex',

        '<>'   => '$ne',
        '!='   => '$ne',
        ':ne'  => '$ne',
        ':not' => '$ne',
    ];


    /**
     * {@inheritdoc}
     */
    public function reset($parts = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function useQuoteIdentifier($flag = true)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBindTypes()
    {
    }

    /**
     * Compile a aggregate command
     *
     * @param Clause $query
     *
     * @return array
     */
    public function compileAggregate(Clause $query)
    {
        return [
            $query->statements['aggregate'] => $query->statements['collection'],
            'query' => $this->compileFilters($query->statements['where'])
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return BulkWrite
     */
    protected function doCompileInsert(Clause $query)
    {
        $bulk = new BulkWrite();

        $bulk->insert(
            $this->compileInsertData($query->statements['values']['data'])
        );

        return $bulk;
    }

    /**
     * {@inheritdoc}
     *
     * @return BulkWrite
     */
    protected function doCompileUpdate(Clause $query)
    {
        $bulk = new BulkWrite();

        if ($query->statements['replace']) {
            $bulk->update(
                $this->compileFilters($query->statements['where']),
                $this->compileUpdateData($query->statements['values']['data']),
                [
                    'upsert' => true,
                    'multi'  => false
                ]
            );
        } else {
            $bulk->update(
                $this->compileFilters($query->statements['where']),
                $this->compileUpdateOperators($query->statements),
                [
                    'multi' => true
                ]
            );
        }

        return $bulk;
    }

    /**
     * {@inheritdoc}
     *
     * @return BulkWrite
     */
    protected function doCompileDelete(Clause $query)
    {
        $bulk = new BulkWrite();

        $bulk->delete(
            $this->compileFilters($query->statements['where'])
        );

        return $bulk;
    }

    /**
     * {@inheritdoc}
     *
     * @return Query
     */
    protected function doCompileSelect(Clause $query)
    {
        $options = [];

        if ($query->statements['columns']) {
            $options['projection'] = $this->compileProjection($query->statements['columns']);
        }

        if ($query->statements['limit']) {
            $options['limit'] = $query->statements['limit'];
        }

        if ($query->statements['offset']) {
            $options['skip'] = $query->statements['offset'];
        }

        if ($query->statements['orders']) {
            $options['sort'] = $this->compileSort($query->statements['orders']);
        }

        $filters = $this->compileFilters($query->statements['where']);

        return new Query($filters, $options);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function compileUpdateData(array $data)
    {
        $parsed = [];

        foreach ($data as $column => $value) {
            $type = isset($data['types'][$column]) ? $data['types'][$column] : true;
            $field = $this->preprocessor->field($column, $type);

            $parsed[$field] = $this->platform->types()->toDatabase($value, $type);
        }

        return $parsed;
    }

    /**
     * Compile document data for insert operation.
     * Unlike Update, the insert data should not be flatten
     *
     * @param array $data
     *
     * @return array
     */
    protected function compileInsertData(array $data)
    {
        $parsed = [];

        foreach ($data as $column => $value) {
            $type = isset($data['types'][$column]) ? $data['types'][$column] : true;

            $field = explode('.', $this->preprocessor->field($column, $type));
            $count = count($field);
            $base = &$parsed;

            for ($i = 0; $i < $count - 1; ++$i) {
                if (!isset($base[$field[$i]])) {
                    $base[$field[$i]] = [];
                }

                $base = &$base[$field[$i]];
            }

            $value = $this->platform->types()->toDatabase($value, $type);
            $base[$field[$i]] = $value;
        }

        return $parsed;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    public function compileFilters(array $filters)
    {
        if (empty($filters)) {
            return [];
        }

        $or = [];
        $and = [];

        foreach ($filters as $filter) {
            $expression = $this->compileSingleFilter($filter);

            // OR a un priorité plus basse que AND
            // Quand on rencontre OR, il sépare la condition en deux parties
            // Avec à gauche tout les anciens AND
            // Et à droite le reste de l'expression (y compris l'expression courante ayant déclanché le OR)
            switch ($filter['glue']) {
                case CompositeExpression::TYPE_AND:
                    $and[] = $expression;
                    break;
                case CompositeExpression::TYPE_OR:
                    if (!empty($and)) {
                        $or[] = $and;
                    }
                    $and = [$expression];
                    break;
            }
        }


        if (empty($or)) {
            return $this->optimizeAndFilters($and);
        }

        $or[] = $and;
        $parts = [];

        foreach ($or as $sub) {
            $parts[] = $this->optimizeAndFilters($sub);
        }

        return [
            '$or' => $parts
        ];
    }

    /**
     * @param array $columns
     *
     * @return array
     */
    public function compileProjection(array $columns)
    {
        $projection = [];

        foreach ($columns as $column) {
            if ($column['column'] === '*') {
                return [];
            }

            $projection[$this->preprocessor->field($column['column'])] = true;
        }

        //If column has been selected, but not _id => do not select _id
        if (!isset($projection['_id'])) {
            $projection['_id'] = false;
        }

        return $projection;
    }

    /**
     * @param array $statements
     *
     * @return array
     */
    public function compileUpdateOperators(array $statements)
    {
        $operators = $statements['update'];

        if (!empty($statements['values'])) {
            $operators['$set'] = $this->compileUpdateData($statements['values']['data']);
        }

        return $operators;
    }

    /**
     * @param array $orders
     *
     * @return array
     */
    public function compileSort(array $orders)
    {
        $sort = [];

        foreach ($orders as $order) {
            $sort[$this->preprocessor->field($order['sort'])] = $order['order'] === 'ASC' ? 1 : -1;
        }

        return $sort;
    }

    /**
     * Build one filter entry
     *
     * @param array $filter
     *
     * @return array|mixed
     */
    protected function compileSingleFilter(array $filter)
    {
        $filter = $this->preprocessor->expression($filter);

        if (isset($filter['nested'])) {
            return $this->compileFilters($filter['nested']);
        } elseif (isset($filter['raw'])) {
            return $filter['raw'];
        } else {
            return $this->compileComparisonOperator(
                $filter['column'],
                $filter['operator'],
                $filter['value'],
                isset($filter['converted']) ? $filter['converted'] : false
            );
        }
    }

    /**
     * Rebuild array of AND filters, in the case
     * there is no need to wrap with '$and' => [...]
     *
     * @param array $filters
     *
     * @return array
     */
    protected function optimizeAndFilters(array $filters)
    {
        $and = [];

        foreach ($filters as $filter) {
            foreach ($filter as $column => $expression) {
                //Could not be optimized => AND used on the same column
                if (isset($and[$column])) {
                    return ['$and' => $filters];
                }

                $and[$column] = $expression;
            }
        }

        return $and;
    }

    /**
     * Get the MongoDB operator for comparison expression
     * @link https://docs.mongodb.com/manual/reference/operator/query-comparison/
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param boolean $converted
     *
     * @return string|array
     */
    protected function compileComparisonOperator($column, $operator, $value, $converted)
    {
        if ($value instanceof ExpressionTransformerInterface) {
            $value->setContext($this, $column, $operator);

            $column   = $value->getColumn();
            $operator = $value->getOperator();
            $value    = $value->getValue();
            $converted = true;
        }

        if (!$converted && $value !== null) {
            $value = $this->autoConvertValue($value);
        }

        if (isset (self::$operatorsMap[$operator])) {
            return $this->compileSimpleOperator($column, self::$operatorsMap[$operator], $value);
        }

        switch ($operator) {
            case '=':
            case ':eq':
                // OR :eq === IN
                if (is_array($value)) {
                    return $this->compileComparisonOperator($column, ':in', $value, true); // $value is always converted here
                }
                return [$column => $value];

            case ':like':
                return [$column => $this->getLikeOperator($value)];

            case 'in':
            case ':in':
                return [$column => ['$in' => $value]];

            case 'notin':
            case '!in':
            case ':notin':
                return [$column => ['$nin' => $value]];

            case 'between':
            case ':between':
                return [
                    '$and' => [
                        [$column => ['$gte' => $value[0]]],
                        [$column => ['$lte' => $value[1]]],
                    ]
                ];

            case '!between':
            case ':notbetween':
                return [
                    '$or' => [
                        [$column => ['$lt' => $value[0]]],
                        [$column => ['$gt' => $value[1]]],
                    ]
                ];

            case '<>':
            case '!=':
            case ':ne':
            case ':not':
                return [$column => ['$ne' => $value]];

            default:
                return $this->compileSimpleOperator($column, $operator, $value);
        }
    }

    /**
     * @param string $column
     * @param string $operator
     * @param string|array $value
     *
     * @return array
     */
    protected function compileSimpleOperator($column, $operator, $value)
    {
        if (is_array($value)) {
            if (empty($value)) {
                $value = null;
            } elseif (count($value) === 1) {
                $value = $value[0];
            } else {
                return [
                    '$or' => array_map(function ($value) use ($column, $operator) {
                        return [$column => [$operator => $value]];
                    }, $value)
                ];
            }
        }

        return [$column => [$operator => $value]];
    }

    /**
     * @param string $value
     *
     * @return array
     */
    protected function getLikeOperator($value)
    {
        return [
            '$regex'   => '^'.strtr($value, ['%' => '.*', '?' => '.']).'$',
            '$options' => 'i'
        ];
    }

    /**
     * Try to resolve type and auto convert value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function autoConvertValue($value)
    {
        if (is_array($value)) {
            foreach ($value as &$e) {
                $e = $this->platform->types()->toDatabase($e);
            }

            return $value;
        }

        return $this->platform->types()->toDatabase($value);
    }

    /**
     * {@inheritdoc}
     */
    public function needsCompile($part)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier($column)
    {
        return $column;
    }
}
