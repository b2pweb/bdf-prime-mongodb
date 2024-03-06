<?php

namespace Bdf\Prime\MongoDB\Query\Compiler;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Expression\ExpressionTransformerInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Mongo grammar for building queries
 */
class MongoGrammar
{
    /**
     * @var PlatformInterface
     */
    private $platform;

    /**
     * @var array<string, string>
     */
    private $operatorsMap = [
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
     * MongoGrammar constructor.
     *
     * @param PlatformInterface $platform
     */
    public function __construct(PlatformInterface $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Get the related platform
     */
    public function platform(): PlatformInterface
    {
        return $this->platform;
    }

    /**
     * Compile Mongo expression which can be nested
     * Expressions are used by aggregation pipeline
     *
     * An expression can be :
     * - A variable, starting with '$' (ex: '$name'), which refers to current document field, and will be resolved using preprocessor
     * - A scalar expression (string, numbers, booleans...) or an object : will be converted to database value
     * - An array :
     *     - An array of expressions : each nested expressions will be converted
     *     - An alias object, in form [ 'alias' => expression ]
     *     - An operator, in form [ '$operator' => expression ]
     *
     * Example:
     * '$firstName' : Get the field 'firstName'
     * 42           : Get the value 42 (integer)
     * ['$name', 5] : Get an array with field 'name' as first value, and 5 as second
     * ['newName' => '$name'] : Get an array with 'newName' as key with field 'name' value
     * ['$concat' => ['$firstName', ' ', '$lastName']] : Concatenate field firstName, space character and lastName
     *
     * @param CompilableClause $query The expression container
     * @param mixed $expression The expression to parse
     *
     * @return mixed
     *
     * @see https://docs.mongodb.com/v3.2/meta/aggregation-quick-reference/#expressions
     */
    public function expression(CompilableClause $query, $expression)
    {
        if (is_string($expression) && $expression[0] === '$') {
            return '$' . $query->preprocessor()->field(substr($expression, 1));
        }

        if (is_scalar($expression)) {
            return $expression;
        }

        if (is_object($expression)) {
            return $this->platform->types()->toDatabase($expression);
        }

        $compiled = [];

        foreach ($expression as $aliasOrOperator => $subExpression) {
            $compiled[$aliasOrOperator] = $this->expression($query, $subExpression);
        }

        return $compiled;
    }

    /**
     * Compile projection expression
     *
     * The columns parameter should be an array of array with keys :
     * - column : The document field name to project (if column is '*', all fields will be selected)
     * - alias  : The alias of the field on the projection
     * - expression : Any expression which can be used on projection
     *
     * /!\ The find() projection do not supports all projection features like alias,
     *     and supports only few projection operators
     *
     * @param CompilableClause $query The query container
     * @param array $columns The columns (or expressions) to project
     *
     * @return array<string, bool|int|string>
     *
     * @see https://docs.mongodb.com/manual/reference/method/db.collection.find/#projection For find projection
     * @see https://docs.mongodb.com/v3.2/reference/operator/aggregation/project/ For aggregation operation
     */
    public function projection(CompilableClause $query, array $columns)
    {
        $projection = [];

        foreach ($columns as $column) {
            if ($column['column'] === '*') {
                return [];
            }

            if (isset($column['expression'])) {
                $projection[$column['column']] = $this->expression($query, $column['expression']);
                continue;
            }

            $field = $query->preprocessor()->field($column['column']);

            if (!empty($column['alias'])) {
                if ($field[0] !== '$') {
                    $field = '$' . $field;
                }

                $projection[$column['alias']] = $field;
                continue;
            }

            $projection[$field] = true;
        }

        //If column has been selected, but not _id => do not select _id
        if (!isset($projection['_id'])) {
            $projection['_id'] = false;
        }

        return $projection;
    }

    /**
     * Compile sort expression
     *
     * @param CompilableClause $query The container query
     * @param array $orders The sort fields, in form [fieldName] => [ASC|DESC]
     *
     * @return array
     *
     * @see https://docs.mongodb.com/manual/reference/method/cursor.sort/#cursor.sort
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sort/
     */
    public function sort(CompilableClause $query, array $orders)
    {
        $sort = [];

        foreach ($orders as $order) {
            $sort[$query->preprocessor()->field($order['sort'])] = strtoupper($order['order']) === 'ASC' ? 1 : -1;
        }

        return $sort;
    }

    /**
     * Compile the $set operator for update a document
     *
     * @param CompilableClause $query The container query
     * @param array $data The values to set
     * @param array $types The types
     *
     * @return array
     *
     * @see https://docs.mongodb.com/manual/reference/operator/update/set/index.html
     */
    public function set(CompilableClause $query, array $data, array $types = [])
    {
        $set = [];

        foreach ($data as $column => $value) {
            $type = $types[$column] ?? true;
            $field = $query->preprocessor()->field($column, $type);

            $set[$field] = $this->platform->types()->toDatabase($value, $type);
        }

        return ['$set' => $set];
    }

    /**
     * Compile query filters
     *
     * @param CompilableClause $query
     * @param array $filters
     *
     * @return array
     *
     * @see https://docs.mongodb.com/manual/reference/operator/query/#query-selectors
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/match/
     */
    public function filters(CompilableClause $query, array $filters)
    {
        if (empty($filters)) {
            return [];
        }

        $or = [];
        $and = [];

        foreach ($filters as $filter) {
            $expression = $this->filter($query, $filter);

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
     * Build one filter entry
     *
     * @param CompilableClause $query
     * @param array $filter
     *
     * @return array|mixed
     */
    private function filter(CompilableClause $query, array $filter)
    {
        $filter = $query->preprocessor()->expression($filter);

        if (isset($filter['nested'])) {
            return $this->filters($query, $filter['nested']);
        } elseif (isset($filter['raw'])) {
            return $filter['raw'];
        } else {
            return $this->comparisonOperator(
                $filter['column'],
                $filter['operator'],
                $filter['value'],
                $filter['converted'] ?? false
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
    private function optimizeAndFilters(array $filters)
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
    private function comparisonOperator($column, $operator, $value, $converted)
    {
        if ($value instanceof ExpressionTransformerInterface) {
            $value->setContext($this, $column, $operator);

            $column   = $value->getColumn();
            $operator = $value->getOperator();
            $value    = $value->getValue();
            $converted = true;
        }

        if (!$converted && $value !== null) {
            $value = $this->convert($value);
        }

        if (isset($this->operatorsMap[$operator])) {
            return $this->simpleOperator($column, $this->operatorsMap[$operator], $value);
        }

        switch ($operator) {
            case '=':
            case ':eq':
                // OR :eq === IN
                if (is_array($value)) {
                    return $this->comparisonOperator($column, ':in', $value, true); // $value is always converted here
                }
                return [$column => $value];

            case 'like':
            case ':like':
                if (is_array($value)) {
                    return [
                    '$or' => array_map(function ($value) use ($column, $operator) {
                        return [$column => $this->like($value)];
                    }, $value)
                    ];
                }

                return [$column => $this->like($value)];

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

            // Cannot use simple operator here : the value is always an array and should not be interpreted as OR
            case '$elemMatch':
                return [$column => ['$elemMatch' => $value]];

            default:
                return $this->simpleOperator($column, $operator, $value);
        }
    }

    /**
     * Compile a simple operator which do not needs extra transformations
     * The result will be in form : [ field => [ $operator => value ] ]
     *
     * If the value is an array, an "OR" operation will be performed
     *
     * Ex:
     * age >= 18                        : [ 'age' => [ '$gte' => 18 ] ]
     * name ~= [ 'r[a-z]+', '[a-z]*5' ] : [ '$or' => [
     *                                          [ 'name' => ['$regex' => 'r[a-z]+']],
     *                                          [ 'name' => ['$regex' => '[a-z]*5']],
     *                                    ]
     *
     * @param string $column Field name to check
     * @param string $operator Comparison operator
     * @param string|array $value The comparison value, or array of values for "OR" comparison
     *
     * @return array The compiled expression
     */
    private function simpleOperator($column, $operator, $value)
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
     * Compile the like operator into a regex
     *
     * @param string $value
     *
     * @return array
     */
    private function like($value)
    {
        return [
            '$regex'   => '^' . strtr($value, ['%' => '.*', '?' => '.']) . '$',
            '$options' => 'i'
        ];
    }

    /**
     * Convert the PHP value to MongoDB value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function convert($value)
    {
        if (is_array($value)) {
            foreach ($value as &$e) {
                $e = $this->platform->types()->toDatabase($e);
            }

            return $value;
        }

        return $this->platform->types()->toDatabase($value);
    }
}
