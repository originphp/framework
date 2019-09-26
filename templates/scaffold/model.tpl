<?php
namespace App\Model;

use Origin\Model\Collection;
use Origin\Model\Entity;
use ArrayObject;

class %model% extends ApplicationModel
{
    /**
     * Before find callback must return a bool. Returning false will stop the find operation.
     *
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeFind(ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * After find callback
     *
     * @param \Origin\Model\Collection $results
     * @param ArrayObject $options
     * @return void
     */
    public function afterFind(Collection $results, ArrayObject $options) : void
    {
    }

    /**
     * Before Validation takes places, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeValidate(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * After Validation callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterValidate(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeSave(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * Before create callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeCreate(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * Before update callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeUpdate(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
    * After create callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterCreate(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
    * After update callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterUpdate(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * After save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterSave(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * Before delete, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return bool
     */
    public function beforeDelete(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * After delete callback
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $sucess wether or not it deleted the record
     * @return void
     */
    public function afterDelete(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
    * After commit callback
    *
    * @param \Origin\Model\Entity $entity
    * @param ArrayObject $options
    * @return bool
    */
    public function afterCommit(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * This is callback is called when an exception is caught
     *
     * @param \Exception $exception
     * @return void
     */
    public function onError(\Exception $exception) : void
    {
    }

    /**
    * After rollback callback
    *
    * @param \Origin\Model\Entity $entity
    * @param ArrayObject $options
    * @return void
    */
    public function afterRollback(Entity $entity, ArrayObject $options) : void
    {
    }

}
