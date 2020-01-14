<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware\Middleware;

class MyMiddleware extends Middleware
{
    public function handle(Request $request) : void
    {
        $request->data('foo', 'bar');
    }
    public function process(Request $request, Response $response) : void
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
        $middleware($request, $response);
    
        $this->assertEquals('bar', $request->data('foo'));
        $this->assertEquals('application/foo', $response->headers('Accept'));
    }
}
