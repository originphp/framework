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

namespace Origin\Model\Behavior;

use Origin\Model\Entity;

/**
 * TimestampBehavior
 * adds timestamp to created and modified fields.
 * Modified is always set, in the case you are importing records and want
 * to preserve the existing modified field, then disable the behavior
 */
class TimestampBehavior extends Behavior
{
    protected $defaultConfig = [
        'created' => 'created',
        'modified' => 'modified',
    ];

    public function initialize(array $config = [])
    {
    }

    public function beforeSave(Entity $entity, array $options = [])
    {
        $model = $this->model();
        $timestamp = date('Y-m-d H:i:s');
        $primaryKey = $model->primaryKey;
        
        $createdField = $this->config['created'];
        if (empty($entity->{$primaryKey})) {
            if ($model->hasField($createdField) and empty($entity->{$createdField})) {
                $entity->set($createdField, $timestamp);
            }
        }

        $modifiedField = $this->config['modified'];
        if ($model->hasField($modifiedField)) {
            $entity->set($modifiedField, $timestamp);
        }
  
        return true;
    }
}
