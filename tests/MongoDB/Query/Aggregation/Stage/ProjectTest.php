<?php

namespace Bdf\Prime\MongoDB\Query\Aggregation\Stage;

use PHPUnit\Framework\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Aggregation
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage
 * @group Bdf_Prime_MongoDB_Query_Aggregation_Stage_Project
 */
class ProjectTest extends TestCase
{
    /**
     *
     */
    public function test_add_without_alias()
    {
        $project = new Project();

        $project->add('name');

        $this->assertEquals(
            [
                ['column' => 'name', 'alias' => null]
            ],
            $project->export()
        );
    }
    /**
     *
     */
    public function test_add_with_alias()
    {
        $project = new Project();

        $project->add('name', 'aliased_name');

        $this->assertEquals(
            [
                ['column' => 'name', 'alias' => 'aliased_name']
            ],
            $project->export()
        );
    }

    /**
     *
     */
    public function test_evaluate_all_parameters()
    {
        $project = new Project();

        $project->evaluate('articles', '$size', '$user.articles');

        $this->assertEquals(
            [
                ['column' => 'articles', 'expression' => ['$size' => '$user.articles']]
            ],
            $project->export()
        );
    }

    /**
     *
     */
    public function test_evaluate_multiple_expressions()
    {
        $project = new Project();

        $project->evaluate('articles', [
            'id'    => '$id',
            '$size' => '$user.articles'
        ]);

        $this->assertEquals(
            [
                [
                    'column' => 'articles',
                    'expression' => [
                        'id'    => '$id',
                        '$size' => '$user.articles'
                    ]
                ]
            ],
            $project->export()
        );
    }
}