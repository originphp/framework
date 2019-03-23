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
     * Callback that is triggered just before the request data is marshalled, when calling
     * model::new or model::patch and passing array
     * This should return the requested data
     *
     * @param array $requestData
     * @return array
     */
    public function beforeMarshal(array $requestData = [])
    {
        return $requestData;
    }

    /**
    * Before find callback. Must return either the query or true to continue
    * @return array|bool query or bool
    */
    public function beforeFind(array $query = [])
    {
        return $query;
    }

    /**
     * After find callback, this should return the results
     * @return \Origin\Model\Entity|\Origin\Model\Collection|array|int $results
     */
    public function afterFind($results)
    {
        return $results;
    }

    /**
     * Before Validation takes places, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @return bool
     */
    public function beforeValidate(Entity $entity)
    {
        return true;
    }

    /**
     * Before Validation takes places, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param bool $success validation result
     * @return bool
     */
    public function afterValidate(Entity $entity, bool $success)
    {
    }

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
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
     * @param \Origin\Model\Entity $entity
     * @param boolean $created if this is a new record
     * @param array $options these were the options passed to save
     * @return void
     */
    public function afterSave(Entity $entity, bool $created, array $options = [])
    {
    }

    /**
     * Before delete, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $cascade
     * @return bool
     */
    public function beforeDelete(Entity $entity, bool $cascade = true)
    {
        return true;
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
    }
}
