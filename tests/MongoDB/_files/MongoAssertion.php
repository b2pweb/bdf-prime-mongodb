<?php

namespace Bdf\Prime;

use MongoDB\Driver\Command;

/**
 * Provide assert for mongo queries
 */
trait MongoAssertion
{
    /**
     * Assert two command objects
     *
     * @param Command|array|object $expected
     * @param Command $current
     *
     * @param string $message
     */
    protected function assertCommand($expected, Command $current, $message = '')
    {
        if (!$expected instanceof Command) {
            $expected = new Command($expected);
        }

        $this->assertInternal($expected, $current, $message ?: 'The two commands are different');
    }

    /**
     * Assert for internal values (i.e. cannot extract attributes from those values)
     *
     * @param mixed $expected
     * @param mixed $current
     *
     * @param string $message
     */
    protected function assertInternal($expected, $current, $message = '')
    {
        $this->assertEquals(print_r($expected, true), print_r($current, true), $message ?: 'The two values are different');
    }
}
