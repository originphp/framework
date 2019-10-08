<?php
namespace App\Model;

use Origin\Model\Collection;
use Origin\Model\Entity;
use ArrayObject;

class %model% extends ApplicationModel
{
    /**
    * This is called when the model is constructed. 
    */
    public function initialize(array $config) : void
    {
        parent::initialize($config);
        %initialize%
    }
}
