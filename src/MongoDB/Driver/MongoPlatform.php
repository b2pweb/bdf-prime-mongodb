<?php

namespace Bdf\Prime\MongoDB\Driver;

use BadMethodCallException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;

/**
 * MongoPlatform
 *
 * @internal
 *
 * @deprecated since 1.3 Will be deleted
 */
class MongoPlatform extends AbstractPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getBooleanTypeDeclarationSQL(array $column)
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegerTypeDeclarationSQL(array $column)
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getBigIntTypeDeclarationSQL(array $column)
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $column)
    {
        throw new BadMethodCallException('Not supported');
    }

    // phpcs:disable
    /**
     * {@inheritdoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $column)
    {
        throw new BadMethodCallException('Not supported');
    }
    // phpcs:enable

    /**
     * {@inheritdoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getClobTypeDeclarationSQL(array $column)
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlobTypeDeclarationSQL(array $column)
    {
        throw new BadMethodCallException('Not supported');
    }

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
    public function getCreateTableSQL(Table $table, $createFlags = self::CREATE_INDEXES)
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentDatabaseExpression(): string
    {
        throw new BadMethodCallException('Not supported');
    }
}
