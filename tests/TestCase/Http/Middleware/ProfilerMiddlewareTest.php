<?php
namespace App\Test\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\TestSuite\OriginTestCase;
use Origin\Http\Middleware\ProfilerMiddleware;

class ProfilerMiddlewareTest extends OriginTestCase
{
    /**
    * @var \Origin\Http\Request
    */
    protected $request = null;

    /**
    * @var \Origin\Http\Response
    */
    protected $response = null;

    protected function startup(): void
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    public function testMiddleware()
    {
        @unlink(LOGS . '/profile.log');

        # Setup Request & Response
        $this->request = new Request();
        $this->response = new Response();

        $this->request->env('REMOTE_ADDR', '192.162.1.20');
        $this->request->env('REQUEST_METHOD', 'GET');

        # Run Middleware
        $middleware = new ProfilerMiddleware();
        $middleware($this->request, $this->response);
        unset($middleware);

        # Actual Test
        $expected = '[' . date('Y-m-d H:i:s') . '] GET http://localhost/';

        // [2019-11-05 12:16:22] GET http://localhost/ 0.0004s 4.27mb
        $contents = file_get_contents(LOGS . '/profile.log');
        $this->assertStringContainsString($expected, $contents);
        $this->assertMatchesRegularExpression('/([0-9\.]+)s ([0-9\.]+)mb/', $contents);
    }
}
