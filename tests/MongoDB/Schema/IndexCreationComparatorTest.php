<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\PHPUnit\TestCase;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Schema
 * @group Bdf_Prime_MongoDB_Schema_IndexCreationComparator
 */
class IndexCreationComparatorTest extends TestCase
{
    /**
     *
     */
    public function test_changed_removed()
    {
        $comparator = new IndexSetCreationComparator(new IndexSet([
            new Index(['col_'], Index::TYPE_SIMPLE, 'name')
        ]));

        $this->assertEmpty($comparator->removed());
        $this->assertEmpty($comparator->changed());
    }
    /**
     *
     */
    public function test_added()
    {
        $comparator = new IndexSetCreationComparator(new IndexSet([
            $index = new Index(['col_'], Index::TYPE_SIMPLE, 'name'),
            new Index(['_id'], Index::TYPE_PRIMARY, '_id_'),
        ]));

        $this->assertEquals([$index], $comparator->added());
    }
}
