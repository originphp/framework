<?php

namespace App\Model;

use Origin\Model\Entity;

class User extends AppModel
{
    public $validationRules = [
        'name' => ['rule' => 'notBlank', 'required' => true, 'on' => 'create'],
        'email' => [
            ['rule' => 'notBlank', 'required' => true, 'on' => 'create'],
            ['rule' => 'email'],
        ],
        'password' => [
            ['rule' => 'notBlank', 'required' => true, 'on' => 'create'],
            ['rule' => 'alphaNumeric', 'message' => 'Alphanumeric characters only'],
            ['rule' => ['minLength', 6], 'message' => 'Min 6 characters'],
            ['rule' => ['maxLength', 8], 'message' => 'Max 8 characters'],
        ],
        'dob' => ['rule' => 'date'],
      ];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->validate($this->validationRules);
        $this->hasMany('Bookmark');
    }

    /**
     * Hash the using the default password_hasher which is what the Auth component uses
     * aswell. Default is blowfish and is considered the most secure. Length will be 60 but
     * to allow for changes in PHP going forward with strong algos, use 255.
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        if (isset($entity->password) and empty($entity->password) === false) {
            $entity->password = password_hash($entity->password, PASSWORD_DEFAULT);
        }

        return true;
    }
}
