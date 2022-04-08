<?php

namespace Bdf\Prime\MongoDB\Collection;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\Expression\ExpressionTransformerInterface;
use Bdf\Prime\Query\Expression\TypedExpressionInterface;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Types\TypeInterface;

/**
 * Preprocessor for declared document mapping
 */
class CollectionPreprocessor implements PreprocessorInterface
{
    protected MongoCollectionInterface $collection;

    /**
     * @param MongoCollectionInterface $collection
     */
    public function __construct(MongoCollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function forInsert(CompilableClause $clause)
    {
        return clone $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forUpdate(CompilableClause $clause)
    {
        $toCompile = clone $clause;

        if ($filters = $this->collection->mapper()->constraints()) {
            $toCompile->whereRaw($filters);
        }

        return $toCompile;
    }

    /**
     * {@inheritdoc}
     */
    public function forDelete(CompilableClause $clause)
    {
        $toCompile = clone $clause;

        if ($filters = $this->collection->mapper()->constraints()) {
            $toCompile->whereRaw($filters);
        }

        return $toCompile;
    }

    /**
     * {@inheritdoc}
     */
    public function forSelect(CompilableClause $clause)
    {
        $toCompile = clone $clause;

        if ($filters = $this->collection->mapper()->constraints()) {
            $toCompile->whereRaw($filters);
        }

        return $toCompile;
    }

    /**
     * {@inheritdoc}
     */
    public function field(string $attribute, &$type = null): string
    {
        if ($type === true) {
            $type = $this->collection->mapper()->fields()->typeOf($attribute, $this->collection->connection()->platform()->types());
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function expression(array $expression): array
    {
        if (isset($expression['column'])) {
            $type = true;

            $expression['column'] = $this->field($expression['column'], $type);

            if ($type instanceof TypeInterface) {
                $value = $expression['value'];

                if ($value instanceof TypedExpressionInterface) {
                    $value->setType($type);
                } elseif (is_array($value)) {
                    /* The value is an array :
                     * - Will result to "into" expression => convert each elements
                     * - Will result to "array" expression (i.e. IN, BETWEEN...) => convert each elements
                     * - Will result to "equal" expression => converted to IN => convert each elements
                     */
                    foreach ($value as &$v) {
                        $v = $this->tryConvertValue($v, $type);
                    }
                } else {
                    $value = $this->tryConvertValue($value, $type);
                }

                $expression['value'] = $value;
                $expression['converted'] = true;
            }
        }

        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function table(array $table): array
    {
        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function root(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
    }

    /**
     * Try to convert the value to DB value
     *
     * @param mixed $value
     * @param TypeInterface $type
     *
     * @return mixed
     */
    protected function tryConvertValue($value, TypeInterface $type)
    {
        if (
            $value instanceof QueryInterface
            || $value instanceof ExpressionInterface
            || $value instanceof ExpressionTransformerInterface
        ) {
            return $value;
        }

        return $type->toDatabase($value);
    }
}
