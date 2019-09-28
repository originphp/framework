<?php
namespace %namespace%\Test\Http\Middleware;

use Origin\TestSuite\OriginTestCase;
use Origin\Http\Request;
use Origin\Http\Response;
use %namespace%\Middleware\%class%Middleware;

class %class%MiddlewareTest extends OriginTestCase
{
    /**
    * @var \Origin\Http\Request
    */
    protected $request = null;

    /**
    * @var \Origin\Http\Response
    */
    protected $response = null;

    public function startup() : void
    {
        $this->request = new Request();
        $this->response = new Response();
    
        // Invoke the middleware
        $middleware = new %class%Middleware();
        $middleware($this->request, $this->response);
    }
}