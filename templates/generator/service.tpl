<?php
namespace %namespace%\Service;

use App\Service\AppService;

class %class%Service extends AppService
{
    /**
    * Dependencies will be sent here from constructor
    */
    public function initialize()
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