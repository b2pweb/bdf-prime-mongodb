<?php

namespace Bdf\Prime\MongoDB\Query\Command;

/**
 * The dropDatabase command drops the current database, deleting the associated data files
 */
class DropDatabase extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'dropDatabase';
    }
}
