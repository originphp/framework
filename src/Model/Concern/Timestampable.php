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
use ArrayObject;

/**
 * Timestampable Behavior
 * adds timestamp to created and modified fields.
 */
trait Timestampable
{
    public function initializeTimestampable()
    {
        /**
         * @todo Need to figure out how not to polute the method list but
         * be able to configure each concern maybe something like
         *
         * $this->configConcern('Timestampable',['created'=>'created'])
         */
        if (!isset($this->createdField)) {
            $this->createdField = 'created';
        }
        if (!isset($this->modifiedField)) {
            $this->modifiedField = 'modified';
        }
        $this->beforeSave('timestambleBeforeSave');
    }

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return bool must return true to continue
     */
    protected function timestambleBeforeSave(Entity $entity, ArrayObject $options) : bool
    {
        $timestamp = date('Y-m-d H:i:s');
        $primaryKey = $this->primaryKey;

        if (empty($entity->$primaryKey)) {
            $this->setTimestamp($entity, $this->createdField, $timestamp);
        }
        $this->setTimestamp($entity, $this->modifiedField, $timestamp);
        
        return true;
    }

    private function setTimestamp(Entity $entity, string $field, string $timestamp)
    {
        if (!$this->hasField($field)) {
            return;
        }
        if (empty($entity->$field) or !in_array($field, $entity->modified())) {
            $entity->set($field, $timestamp);
        }
    }
}
