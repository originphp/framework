<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

use ArrayObject;
use Origin\Model\Entity;

/**
 * Timestampable Behavior
 * adds timestamp to created and modified fields.
 *
 * @todo an idea on how to go about configuration
 *
 * $this->timestampConfig([
 *  'created' => 'created_at',
 *  'modified' => 'modified_at'
 * ]);
 */
trait Timestampable
{
    /**
     * @var string
     */
    private $timestamp;

    public function initializeTimestampable()
    {
        if (! isset($this->createdField)) {
            $this->createdField = 'created';
        }
        if (! isset($this->modifiedField)) {
            $this->modifiedField = 'modified';
        }
        $this->timestamp = date('Y-m-d H:i:s');
        $this->beforeCreate('timestambleBeforeCreate');
        $this->beforeSave('timestambleBeforeSave');
    }

    /**
    * Before create callback
    *
    * @param \Origin\Model\Entity $entity
    * @param ArrayObject $options
    * @return void
    */
    protected function timestambleBeforeCreate(Entity $entity, ArrayObject $options): void
    {
        $this->setTimestamp($entity, $this->createdField);
    }

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    protected function timestambleBeforeSave(Entity $entity, ArrayObject $options): void
    {
        $this->setTimestamp($entity, $this->modifiedField);
    }

    /**
     * Sets the timestamp
     *
     * @param \Origin\Model\Entity $entity
     * @param string $field
     * @return void
     */
    private function setTimestamp(Entity $entity, string $field): void
    {
        if (! $this->hasField($field)) {
            return;
        }
        if (empty($entity->$field) || ! in_array($field, $entity->modified())) {
            $entity->set($field, $this->timestamp);
        }
    }
}
