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
use Origin\Http\BaseApplication;
use Origin\Exception\InvalidArgumentException;

class MyMiddleware extends Middleware
{
    public function process(Request $request, Response $response) : Response
    {
        $request->data('foo', 'bar');
        return $response;
    }
}
class Application extends BaseApplication
{
    public function initialize()
    {
        $this->addMiddleware(new MyMiddleware());
    }

    public function getStack(){
        return $this->middlewareStack;
    }
}

class MiddlwareTest extends \PHPUnit\Framework\TestCase
{
    public function testMiddleware()
    {
        $middleware = new Middleware();
        $this->assertInstanceOf(Response::class, $middleware->process(new Request(), new Response()));
    }
    public function testExecution()
    {
        $request = new Request();
        new Application($request, new Response());
        $this->assertEquals('bar', $request->data('foo'));
    }

    public function testUnkownMiddleware(){
        $application = new Application(new Request(), new Response());
        $this->expectException(InvalidArgumentException::class);
        $application->loadMiddleware('FormSecurity');
    }

    public function testLoadMiddleware(){
        $application = new Application(new Request(), new Response());
        $application->loadMiddleware('Origin\Test\Middleware\MyMiddleware');
        $this->assertNotEmpty($application->getStack());
    }
}
