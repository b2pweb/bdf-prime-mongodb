<?php

namespace Bdf\Prime\MongoDB\Driver;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * MongoSchemasManager
 *
 * @property MongoConnection $_conn
 *
 * @deprecated since 1.3 Will be deleted
 */
class MongoSchemasManager extends AbstractSchemaManager
{
    // phpcs:disable
    /**
     * Gets Table Column Definition.
     *
     * @param array $tableColumn
     *
     * @return \Doctrine\DBAL\Schema\Column
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
    }
    // phpcs:enable

    /**
     * {@inheritdoc}
     */
    public function listTableNames()
    {
        $list = $this->_conn->runCommand('listCollections');

        $collections = [];

        foreach ($list as $info) {
            $collections[] = $info->name;
        }

        return $collections;
    }
}
