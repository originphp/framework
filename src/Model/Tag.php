<?php

namespace App\Model;

class Tag extends AppModel
{
    public $validationRules = [];
    public $displayField = 'title';

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->validate($this->validationRules);
        $this->hasAndBelongsToMany('Bookmark');
    }
}
