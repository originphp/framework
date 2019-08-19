<?php
namespace %namespace%\Test\Controller\Component;

use Origin\TestSuite\OriginTestCase;
use Origin\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use App\Controller\Component\%class%Component;

class %class%ComponentTest extends OriginTestCase
{
    /**
    * @var \App\Controller\Component\%class%Component
    */
    protected $%class% = null;

    public function startup()
    {
        parent::startup();
        $request = new Request();
        $response =  new Response();
        $controller = new Controller($request,$response);
        $this->%class% = new %class%Component($controller);
    }
}