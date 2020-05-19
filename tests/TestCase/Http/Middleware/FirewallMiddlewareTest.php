<?php
namespace App\Test\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\TestSuite\OriginTestCase;
use Origin\Http\Exception\ForbiddenException;
use Origin\Http\Middleware\FirewallMiddleware;

class MockFirewallMiddleware extends FirewallMiddleware
{
    public function blacklist(string $ip)
    {
        $this->blacklist[] = $ip;
    }

    public function whitelist(string $ip)
    {
        $this->whitelist[] = $ip;
    }
}

class FirewallMiddlewareTest extends OriginTestCase
{
    /**
    * @var \Origin\Http\Request
    */
    protected $request = null;

    /**
    * @var \Origin\Http\Response
    */
    protected $response = null;

    protected function setUp(): void
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    public function testNormal()
    {
        // Invoke the middleware
        $middleware = new MockFirewallMiddleware();
        $this->request->env('REMOTE_ADDR', '192.162.1.20');

        $result = $middleware($this->request, $this->response);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testBlacklisted()
    {
        // Invoke the middleware
        $middleware = new MockFirewallMiddleware();

        $middleware->blacklist('192.162.1.20');
        $this->request->env('REMOTE_ADDR', '192.162.1.20');

        $this->expectException(ForbiddenException::class);
        $middleware($this->request, $this->response);
    }

    public function testWhitelisted()
    {
        // Invoke the middleware
        $middleware = new MockFirewallMiddleware();
        $this->request->env('REMOTE_ADDR', '192.162.1.20');
        $middleware->whitelist('192.162.1.20');
        $middleware->blacklist('192.162.1.20'); // Whitelist overides blacklist
        $result = $middleware($this->request, $this->response);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testNotWhitelisted()
    {
        $middleware = new MockFirewallMiddleware();
        $middleware->whitelist('192.162.1.20');
        $this->request->env('REMOTE_ADDR', '192.162.1.21');
        $this->expectException(ForbiddenException::class);
        $middleware($this->request, $this->response);
    }
}
