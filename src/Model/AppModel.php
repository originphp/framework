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
    * Before find callback. Must return either the query or true to continue
    * @return array|bool
    */
    public function beforeFind($query = [])
    {
        return $query;
    }

    /**
     * After find callback.
     * @return array|int $results
     */
    public function afterFind($results)
    {
        return $results;
    }

    /**
     * Before validate callback
     * @return bool must return true to continue
     */
    public function beforeValidate(Entity $entity)
    {
        return true;
    }

    /**
     * After validate callback
     * @param Entity $entity
     * @return void
     */
    public function afterValidate(Entity $entity)
    {
        parent::afterValidate($entity);
    }

    /**
     * Before save callback
     *
     * @param Entity $entity
     * @param array $options
     * @return bool must return true to continue
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        return true;
    }

    /**
     * After save callback
     *
     * @param Entity $entity
     * @param boolean $created
     * @param array $options
     * @return void
     */
    public function afterSave(Entity $entity, bool $created, array $options = [])
    {
    }

    /**
     * Before delete callback
     *
     * @param boolean $cascade
     * @return bool must return true to continue
     */
    public function beforeDelete(bool $cascade = true)
    {
        return true;
    }
    /**
     * After delete callback
     */
    public function afterDelete()
    {
    }
}
