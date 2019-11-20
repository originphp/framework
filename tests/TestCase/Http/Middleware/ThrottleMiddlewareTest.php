<?php
namespace App\Test\Http\Middleware;

use Origin\Cache\Cache;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\TestSuite\OriginTestCase;
use Origin\Http\Middleware\ThrottleMiddleware;
use Origin\Http\Exception\ServiceUnavailableException;

class ThrottleMiddlewareTest extends OriginTestCase
{
    /**
    * @var \Origin\Http\Request
    */
    protected $request = null;

    /**
    * @var \Origin\Http\Response
    */
    protected $response = null;

    protected function setUp() : void
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    public function testMiddlewareExecution()
    {
        # Setup the Request
        $this->request->env('REMOTE_ADDR', '192.162.1.20');

        $middleware = new ThrottleMiddleware();
        $result = $middleware($this->request, $this->response);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testMiddlewareToManyRequests()
    {
        # Ban Requester
        $this->request->env('REMOTE_ADDR', '192.162.1.20');
        $middleware = new ThrottleMiddleware(['limit' => 0,'period' => 1]);
        $middleware($this->request, $this->response);

        # Now Test Banning Working
        $this->expectException(ServiceUnavailableException::class);
        $middleware = new ThrottleMiddleware(['limit' => 0,'period' => 1]);
        $middleware($this->request, $this->response);
    }

    public function tearDown() : void
    {
        Cache::clear(['config' => 'throttle']);
        @unlink(TMP . '/blacklist.php');
    }
}
