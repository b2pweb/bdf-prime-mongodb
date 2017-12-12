<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use Bdf\PHPUnit\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Aggregation
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage_Group
 */
class GroupTest extends TestCase
{
    /**
     *
     */
    public function test_constructor()
    {
        $group = new Group('field');

        $this->assertEquals(['_id' => 'field'], $group->export());
    }

    /**
     *
     */
    public function test_accumulator_with_field()
    {
        $group = new Group(null);

        $this->assertEquals(
            [
                '_id' => null,
                'aggr' => ['$avg' => '$field']
            ],
            $group->accumulator('aggr', '$avg', 'field')->export()
        );
    }

    /**
     *
     */
    public function test_accumulator_with_constant()
    {
        $group = new Group(null);

        $this->assertEquals(
            [
                '_id'   => null,
                'count' => ['$sum' => 1]
            ],
            $group->accumulator('count', '$sum', 1)->export()
        );
    }

    /**
     * @dataProvider provideMake
     */
    public function test_make($expected, $expression, $operations)
    {
        $this->assertEquals(
            $expected,
            Group::make($expression, $operations)->export()
        );
    }

    /**
     *
     */
    public function provideMake()
    {
        return [
            [['_id' => null], null, null],
            [['_id' => 'field'], 'field', null],
            [['_id' => null, 'agg' => ['$sum' => '$field']], null, ['agg' => ['sum' => 'field']]],
            [['_id' => null, 'agg' => ['$avg' => '$field']], null, ['agg' => ['avg' => 'field']]],
            [['_id' => null, 'agg' => ['$first' => '$field']], null, ['agg' => ['first' => 'field']]],
            [['_id' => null, 'agg' => ['$last' => '$field']], null, ['agg' => ['last' => 'field']]],
            [['_id' => null, 'agg' => ['$max' => '$field']], null, ['agg' => ['max' => 'field']]],
            [['_id' => null, 'agg' => ['$min' => '$field']], null, ['agg' => ['min' => 'field']]],
            [['_id' => null, 'agg' => ['$push' => '$field']], null, ['agg' => ['push' => 'field']]],
            [['_id' => null, 'agg' => ['$addToSet' => '$field']], null, ['agg' => ['addToSet' => 'field']]],
            [['_id' => null, 'agg' => ['$stdDevPop' => '$field']], null, ['agg' => ['stdDevPop' => 'field']]],
            [['_id' => null, 'agg' => ['$stdDevSamp' => '$field']], null, ['agg' => ['stdDevSamp' => 'field']]],
        ];
    }
}
