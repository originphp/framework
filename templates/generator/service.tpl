<?php
namespace %namespace%\Service;

use App\Service\ApplicationService;
use Origin\Service\Result;

class %class%Service extends ApplicationService
{
    /**
    * Dependencies will be sent here from constructor
    */
    protected function initialize() : void
    {
  
    }

    /*
    * Service logic goes here and return a result object or null
    */
    protected function execute() : ?Result
    {
        
        return $this->result([
            'success' => true,
            'data' => []
            ]);
    }
}