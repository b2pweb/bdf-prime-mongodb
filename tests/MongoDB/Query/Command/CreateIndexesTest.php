<?php

namespace Bdf\Prime\MongoDB\Query\Command;


use Bdf\PHPUnit\TestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Query
 * @group Bdf_Prime_MongoDB_Query_Command
 * @group Bdf_Prime_MongoDB_Query_Command_CreateIndexes
 */
class CreateIndexesTest extends TestCase
{
    public function test_defaults()
    {
        $this->assertEquals([
            'createIndexes' => 'collection',
            'indexes'       => []
        ], (new CreateIndexes('collection'))->document());
    }

    public function test_add()
    {
        $this->assertEquals([
            'createIndexes'  => 'collection',
            'indexes' => [
                [
                    'key' => [
                        'name' => 1,
                        'date' => -1
                    ],
                    'name' => 'search'
                ]
            ]
        ], (new CreateIndexes('collection'))
            ->add('search', [
                'name' => 1,
                'date' => -1
            ])
            ->document()
        );
    }

    public function test_unique()
    {
        $this->assertEquals([
            'createIndexes'  => 'collection',
            'indexes' => [
                [
                    'key' => [
                        'login' => 1,
                    ],
                    'name'   => 'unq_log',
                    'unique' => true
                ]
            ]
        ], (new CreateIndexes('collection'))
            ->add('unq_log', [
                'login' => 1,
            ])
            ->unique()
            ->document()
        );
    }

    /**
     *
     */
    public function test_add_two_indexes()
    {
        $this->assertEquals([
            'createIndexes'  => 'collection',
            'indexes' => [
                [
                    'key' => [
                        'login' => 1,
                    ],
                    'name'   => 'unq_log',
                    'unique' => true
                ],
                [
                    'key' => [
                        'name' => -1,
                    ],
                    'name'   => 'other',
                    'collation' => [
                        'strength' => 1
                    ]
                ]
            ]
        ], (new CreateIndexes('collection'))
            ->add('unq_log', ['login' => 1])->unique()
            ->add('other', ['name' => -1])->collation(['strength' => 1])
            ->document()
        );
    }
}
