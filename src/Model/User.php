<?php

namespace App\Model;

use Origin\Model\Entity;

class User extends AppModel
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->validate('name', [
            ['rule' => 'notBlank'],
            ['rule' => ['goal2',1,2,3]],
        ]);
        //  $this->validate('name', 'notBlank');
        $this->validate('email', [
            ['rule' => 'notBlank'],
            ['rule' => 'email'],
        ]);
        $this->validate('password', [
            ['rule' => 'notBlank'],
            ['rule' => 'alphaNumeric', 'message' => 'Alphanumeric characters only'],
            ['rule' => ['minLength', 6], 'message' => 'Min 6 characters'],
            ['rule' => ['maxLength', 8], 'message' => 'Max 8 characters'],
        ]);
        $this->validate('dob', 'date');

        $this->hasMany('Bookmark');
    }

    public function goal1()
    {
        pr('goal1');
        pr(func_get_args());
        return false;
    }

    public function goal2()
    {
        pr(func_get_args());
        return false;
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
