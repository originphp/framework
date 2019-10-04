<?php

namespace App\Model;

use Origin\Model\Collection;
use Origin\Model\Model;
use Origin\Model\Entity;
use ArrayObject;

class ApplicationModel extends Model
{
    /**
     * This is callback is called when an exception is caught
     *
     * @param \Exception $exception
     * @return void
     */
    public function onError(\Exception $exception) : void
    {
    }
}
