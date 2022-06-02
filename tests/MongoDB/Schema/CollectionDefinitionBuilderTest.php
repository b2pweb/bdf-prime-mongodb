<?php

namespace MongoDB\Schema;

use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\MongoDB\Schema\CollectionDefinitionBuilder;
use PHPUnit\Framework\TestCase;

class CollectionDefinitionBuilderTest extends TestCase
{
    public function test_empty()
    {
        $builder = new CollectionDefinitionBuilder('my_collection');
        $collection = $builder->build();

        $this->assertEquals('my_collection', $collection->name());
        $this->assertEmpty($collection->options());
        $this->assertCount(1, $collection->indexes()->all());
        $this->assertArrayHasKey('_id_', $collection->indexes()->all());
        $this->assertEquals('_id_', $collection->indexes()->primary()->name());
        $this->assertEquals(['_id'], $collection->indexes()->primary()->fields());
    }

    public function test_with_options()
    {
        $builder = new CollectionDefinitionBuilder('my_collection');
        $collection = $builder
            ->capped()
            ->size(1024*1024*5)
            ->validator(['foo' => ['$exists' => true]])
            ->validationAction('error')
            ->validationLevel('strict')
            ->build();

        $this->assertEquals('my_collection', $collection->name());
        $this->assertEquals([
            'capped' => true,
            'size' => 1024*1024*5,
            'validator' => ['foo' => ['$exists' => true]],
            'validationAction' => 'error',
            'validationLevel' => 'strict',
        ], $collection->options());
        $this->assertCount(1, $collection->indexes()->all());
        $this->assertArrayHasKey('_id_', $collection->indexes()->all());
        $this->assertEquals('_id_', $collection->indexes()->primary()->name());
        $this->assertEquals(['_id'], $collection->indexes()->primary()->fields());
        $this->assertEquals(['foo' => ['$exists' => true]], $collection->option('validator'));
    }

    public function test_with_indexes()
    {
        $builder = new CollectionDefinitionBuilder('my_collection');

        $builder->indexes(function (IndexBuilder $builder) {
            $builder->add()->on(['foo', 'bar'])->unique();
        });

        $builder->addIndex()->on('baz');

        $collection = $builder->build();

        $this->assertEquals('my_collection', $collection->name());
        $this->assertEmpty($collection->options());
        $this->assertCount(3, $collection->indexes()->all());
        $this->assertArrayHasKey('_id_', $collection->indexes()->all());

        $indexes = array_values($collection->indexes()->all());

        $this->assertEquals(['foo', 'bar'], $indexes[1]->fields());
        $this->assertTrue($indexes[1]->unique());
        $this->assertEquals(['baz'], $indexes[2]->fields());
        $this->assertFalse($indexes[2]->unique());
    }
}
