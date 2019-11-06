<?php

namespace Bdf\Prime\MongoDB\Schema;

use Bdf\Prime\Schema\Adapter\AbstractIndex;

/**
 * Adapt result of command "listIndexes" to IndexInterface
 *
 * @link https://docs.mongodb.com/manual/reference/command/listIndexes/
 */
class MongoIndex extends AbstractIndex
{
    const PRIMARY = '_id_';

    /**
     * @var array
     */
    private $data;


    /**
     * MongoIndex constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->data['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        if ($this->name() === self::PRIMARY) {
            return self::TYPE_PRIMARY;
        }

        if (!empty($this->data['unique'])) {
            return self::TYPE_UNIQUE;
        }

        return self::TYPE_SIMPLE;
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        return array_keys($this->data['key']);
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return array_diff_key($this->data, [
            'key' => true,
            'unique' => true,
            'name' => true,
            'v' => true,
            'ns' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function fieldOptions($field)
    {
        $options = [];

        if ($this->data['key'][$field] === -1) {
            $options['order'] = 'DESC';
        }

        if (is_string($this->data['key'][$field])) {
            $options['type'] = $this->data['key'][$field];
        }

        return $options;
    }
}
