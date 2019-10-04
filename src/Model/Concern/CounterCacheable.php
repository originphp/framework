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
declare(strict_types = 1);
namespace Origin\Model\Concern;

use Origin\Model\Entity;
use Origin\Utility\Inflector;
use ArrayObject;

/**
 * In Book belongsTo Author set counterCache = true or fieldName
 */
trait CounterCacheable
{
    private $counterCacheBelongsTo = null;
    private $counterCacheFields = null;

    /**
     * Enables the counter cache on this model
     *
     * @return void
     */
    protected function enableCounterCache() : void
    {
        $this->afterSave('counterCacheAfterSave');
        $this->afterDelete('counterCacheAfterDelete');
    }

    /**
     * Get the data, if configuration is same then used cached
     *
     * @return array
     */
    private function getFields() : array
    {
        $belongsTo = $this->association('belongsTo');
        // Check internal cache
        if ($this->counterCacheBelongsTo === $belongsTo) {
            return $this->counterCacheFields;
        }
        // Process
        $this->counterCacheBelongsTo = $belongsTo;
        $this->counterCacheFields = [];

        foreach ($belongsTo as $alias => $config) {
            if (! empty($config['counterCache'])) {
                $field = $config['counterCache'];
                if ($field === true) {
                    $name = Inflector::plural($this->name);
                    $field = Inflector::underscored($name) . '_count';
                }
                $this->counterCacheFields[$alias] = [
                    'field' => $field,
                    'foreignKey' => $config['foreignKey'],
                    'name' => Inflector::underscored($alias),
                ];
            }
        }

        return $this->counterCacheFields;
    }

    /**
     * After save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    protected function counterCacheAfterSave(Entity $entity, ArrayObject $options) : void
    {
        $items = $this->getFields();
        
        foreach ($items as $alias => $config) {
            if ($this->$alias->hasField($config['field'])) {
                $id = $entity->get($config['foreignKey']);
                $this->$alias->increment($config['field'], $id);
            }
        }
    }
   
    /**
     * After delete
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    protected function counterCacheAfterDelete(Entity $entity, ArrayObject $options) : void
    {
        $items = $this->getFields();
        foreach ($items as $alias => $config) {
            if ($this->$alias->hasField($config['field'])) {
                $id = $entity->get($config['foreignKey']);
                $this->$alias->decrement($config['field'], $id);
            }
        }
    }
}
