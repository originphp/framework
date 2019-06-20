<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Middleware;

use Origin\Http\Middleware;
use Origin\Http\Request;
use Origin\Http\Response;

class MyMiddleware extends Middleware
{
    public function startup(Request $request)
    {
        $request->data('foo', 'bar');
    }
    public function shutdown(Request $request, Response $response)
    {
        $response->header('Accept', 'application/foo');
    }
}

class MiddlwareTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoke()
    {
        $middleware = new Middleware();
        $this->assertInstanceOf(Response::class, $middleware(new Request(), new Response()));
    }

    public function testExecution()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MyMiddleware();
        $middleware->startup($request);
        $middleware->shutdown($request, $response);

        $this->assertEquals('bar', $request->data('foo'));
        $this->assertEquals('application/foo', $response->headers('Accept'));
    }
}
