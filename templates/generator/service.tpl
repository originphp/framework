<?php
namespace %namespace%\Service;

use App\Service\ApplicationService;

class %class%Service extends ApplicationService
{
    /**
    * Dependencies will be sent here from constructor
    */
    public function initialize() : void
    {
  
    }

    /*
    * Service logic goes here and return a result object
    */
    public function execute()
    {
        
        return $this->result([
            'success' => true,
            'data' => []
            ]);
    }
}