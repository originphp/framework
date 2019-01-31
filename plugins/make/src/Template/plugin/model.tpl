<?php 
namespace %plugin%\Model;

use App\Model\AppModel;
use Origin\Model\Entity;

class %plugin%AppModel extends AppModel
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
    * @return array|bool
    */
    public function beforeFind($query = [])
    {
        if (!$query = parent::beforeFind($query)) {
            return false;
        }
        return $query;
    }

    /**
     * After find callback.
     * @return array|int $results
     */
    public function afterFind($results)
    {
        $results = parent::afterFind($results);
        return $results;
    }

    /**
     * Before validate callback
     * @return bool must return true to continue
     */
    public function beforeValidate(Entity $entity)
    {
        if (!parent::beforeValidate($entity)) {
            return false;
        }
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
        if (!parent::beforeSave($entity)) {
            return false;
        }
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
        parent::afterSave($entity, $created, $options);
    }

    /**
     * Before delete callback
     *
     * @param boolean $cascade
     * @return bool must return true to continue
     */
    public function beforeDelete(bool $cascade = true)
    {
        if (!parent::beforeDelete($cascade)) {
            return false;
        }

        return true;
    }
    /**
     * After delete callback
     */
    public function afterDelete()
    {
    }
}
