<?php
namespace %namespace%\Model;

use Origin\Model\Entity;

class %class% extends AppModel
{
    /**
    * This is called when the model is constructed. 
    */
    public function initialize(array $config)
    {
        parent::initialize($config);
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
     * Before create callback
     *
     * @param \Origin\Model\Entity $entity
     * @return bool must return true to continue
     */
    public function beforeCreate(Entity $entity)
    {
        return true;
    }

    /**
     * Before update callback
     *
     * @param \Origin\Model\Entity $entity
     * @return bool must return true to continue
     */
    public function beforeUpdate(Entity $entity)
    {
        return true;
    }

    /**
    * After create callback
    *
    * @param \Origin\Model\Entity $entity
    * @return void
    */
    public function afterCreate(Entity $entity)
    {
    }

    /**
    * After update callback
    *
    * @param \Origin\Model\Entity $entity
    * @return void
    */
    public function afterUpdate(Entity $entity)
    {
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

    /**
    * After commit callback
    *
    * @param \Origin\Model\Entity $entity
    * @return void
    */
    public function afterCommit(Entity $entity)
    {
    }

    /**
    * After rollback callback
    *
    * @param \Origin\Model\Entity $entity
    * @return void
    */
    public function afterRollback(Entity $entity)
    {
    }
}