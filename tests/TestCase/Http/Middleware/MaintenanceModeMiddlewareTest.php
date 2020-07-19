<?php
namespace App\Test\Http\Middleware;

use Origin\TestSuite\OriginTestCase;
use Origin\Http\Request;
use Origin\Http\Response;
use App\Http\Middleware\MaintenanceModeMiddleware;

class MaintenanceModeMiddlewareTest extends OriginTestCase
{
    /**
    * @var \Origin\Http\Request
    */
    protected $request = null;

    /**
    * @var \Origin\Http\Response
    */
    protected $response = null;

    protected function startup() : void
    {
        $this->request = new Request();
        $this->response = new Response();
    
        // Invoke the middleware
        $middleware = new MaintenanceModeMiddleware();
        $middleware($this->request, $this->response);
    }
}