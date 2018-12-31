<?php

namespace App\Model;

use Origin\Model\Model;
use Origin\Model\Entity;

class AppModel extends Model
{
    public function initialize(array $config)
    {
        $this->loadBehavior('Timestamp');
    }

    /**
     * Return either the query or true.
     */
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
}
