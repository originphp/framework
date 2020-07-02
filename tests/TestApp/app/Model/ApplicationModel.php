<?php

namespace App\Model;

use Origin\Model\Model;

class ApplicationModel extends Model
{
    protected function initialize(): void
    {
        $this->onError('errorHandler');
    }
    
    /**
     * This is callback is called when an exception is caught
     *
     * @param \Exception $exception
     * @return void
     */
    public function errorHandler(\Exception $exception): void
    {
    }
}
