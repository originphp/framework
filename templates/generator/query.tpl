<?php
namespace %namespace%\Model\Query;

use Origin\Model\Query\QueryObject;
use Origin\Model\Model;

class %class%Query extends QueryObject
{
    protected function initialize(Model $model) : void
    {
        $this->Model = $model;
    }

    public function execute()
    {

    }
}