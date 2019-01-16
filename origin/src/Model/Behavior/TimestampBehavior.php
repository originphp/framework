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

class TimestampBehavior extends Behavior
{
    protected $defaultConfig = [
        'created' => 'created',
        'modified' => 'modified',
    ];

    public function initialize(array $config = [])
    {
        /*
         * @todo pending intnialize default config merge trait. This can be removed once
         * implemented.
         */
        $this->config = array_merge($this->defaultConfig, $this->config);
    }

    public function beforeSave(Entity $entity, $options = array())
    {
        $model = $this->model();
        $timestamp = date('Y-m-d H:i:s');
        $primaryKey = $model->primaryKey;
        $createdField = $this->config['created'];
        if (!$entity->hasProperty($primaryKey) or empty($entity->{$primaryKey })) {
            if ($model->hasField($createdField) and empty($entity->{$createdField})) {
                $entity->set($createdField, $timestamp);
            }
        }

        $modifiedField = $this->config['modified'];
        if ($model->hasField($modifiedField) and empty($entity->{$modifiedField})) {
            $entity->set($modifiedField, $timestamp);
        }

        return true;
    }
}
