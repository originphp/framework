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

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Core\ConfigTrait;

class Behavior
{
    use ConfigTrait;
    
    /**
     * Holds the model for this behavior.
     */
    protected $model = null;

    public function __construct(Model $model, array $config = [])
    {
        $this->model = $model;

        $this->config($config);
        $this->initialize($config);
    }

    public function initialize(array $config)
    {
    }

    public function beforeFind($query = [])
    {
        return $query;
    }

    public function afterFind($results)
    {
        return $results;
    }

    /**
     * This must return true;.
     *
     * @return bool true
     */
    public function beforeValidate(Entity $entity)
    {
        return true;
    }

    /**
     * Called after validating data.
     */
    public function afterValidate(Entity $entity)
    {
    }

    public function beforeSave(Entity $entity, array $options = [])
    {
        return true;
    }

    public function afterSave(Entity $entity, bool $created, array $options = [])
    {
    }

    public function beforeDelete(bool $cascade = true)
    {
        return true;
    }

    public function afterDelete()
    {
    }

    public function model()
    {
        return $this->model;
    }
}
