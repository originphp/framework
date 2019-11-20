<?php
namespace App\Test\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\TestSuite\OriginTestCase;
use Origin\Http\Middleware\AccessLogMiddleware;

class AccessLogMiddlewareTest extends OriginTestCase
{
    /**
    * @var \Origin\Http\Request
    */
    protected $request = null;

    /**
    * @var \Origin\Http\Response
    */
    protected $response = null;

    public function testRun()
    {
        @unlink(LOGS . '/access.log');

        # Setup Request & Response
        $this->request = new Request();
        $this->response = new Response();

        $this->request->env('REMOTE_ADDR', '192.162.1.20');
        $this->request->env('REQUEST_METHOD', 'GET');
        $this->request->env('REQUEST_URI', '/bookmarks');
        $this->request->env('SERVER_PROTOCOL', 'HTTP/1.1');
        $this->response->statusCode(200);
        $this->response->body('hello world');

        # Run Middleware
        $middleware = new AccessLogMiddleware();
        $middleware($this->request, $this->response);

        # Actual Test
        $expected = '192.162.1.20 - [' . date('d/M/Y:H:i:s O') . '] "GET /bookmarks HTTP/1.1" 200 11';
        $this->assertStringContainsString($expected, file_get_contents(LOGS . '/access.log'));
    }
}
