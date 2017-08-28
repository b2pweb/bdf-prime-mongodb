<?php

namespace Bdf\Prime\MongoDB\Driver;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;

/**
 * MongoPlatform
 *
 * @internal
 */
class MongoPlatform extends AbstractPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getBooleanTypeDeclarationSQL(array $columnDef) {}

    /**
     * {@inheritdoc}
     */
    public function getIntegerTypeDeclarationSQL(array $columnDef) {}

    /**
     * {@inheritdoc}
     */
    public function getBigIntTypeDeclarationSQL(array $columnDef) {}

    /**
     * {@inheritdoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $columnDef) {}

    /**
     * {@inheritdoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef) {}

    /**
     * {@inheritdoc}
     */
    protected function initializeDoctrineTypeMappings() {}

    /**
     * {@inheritdoc}
     */
    public function getClobTypeDeclarationSQL(array $field) {}

    /**
     * {@inheritdoc}
     */
    public function getBlobTypeDeclarationSQL(array $field) {}

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mongodb';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSavepoints()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateTableSQL(Table $table, $createFlags = self::CREATE_INDEXES) {}
}
