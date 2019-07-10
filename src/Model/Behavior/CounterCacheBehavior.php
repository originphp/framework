<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);

namespace Origin\Model\Behavior;

use Origin\Model\Behavior\Behavior;
use Origin\Model\Entity;
use Origin\Core\Inflector;

/**
 * In Book belongsTo Author set counterCache = true or fieldName
 */
class CounterCacheBehavior extends Behavior
{
    protected $belongsTo = null;
    protected $fields = null;

    /**
     * Get the data, if configuration is same then used cached
     *
     * @return array
     */
    public function getFields() : array
    {
        $belongsTo = $this->model()->association('belongsTo');
        if ($this->belongsTo === $belongsTo) {
            return $this->fields;
        }
        $this->belongsTo = $belongsTo;
        $this->fields = [];

        foreach ($belongsTo as $alias => $config) {
            if (!empty($config['counterCache'])) {
                $field = $config['counterCache'];
                if ($field === true) {
                    $field = Inflector::underscore($field) . '_count';
                }
                $this->fields[$alias] = [
                    'field' => $field,
                    'foreignKey' => $config['foreignKey'],
                    'name' => Inflector::underscore($alias)
                ];
            }
        }
        return $this->fields;
    }

    /**
     * After save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $created if this is a new record
     * @param array $options these were the options passed to save
     * @return void
     */
    public function afterSave(Entity $entity, bool $created, array $options = [])
    {
        $items = $this->getFields();
        
        foreach ($items as $alias => $config) {
            $model = $this->model()->{$alias};
            if ($model->hasField($config['field'])) {
                $id = $entity->get($config['foreignKey']);
                $model->increment($config['field'], $id);
            }
        }
    }
   
    /**
     * After delete
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $sucess wether or not it deleted the record
     * @return bool
     */
    public function afterDelete(Entity $entity, bool $success)
    {
        $items = $this->getFields();
        foreach ($items as $alias => $config) {
            $model = $this->model()->{$alias};
            if ($model->hasField($config['field'])) {
                $id = $entity->get($config['foreignKey']);
                $model->decrement($config['field'], $id);
            }
        }
    }
}
