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

namespace Origin\Test\Http;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware;
use Origin\Http\BaseApplication;
use Origin\Http\MiddlewareRunner;
use Origin\Exception\InvalidArgumentException;

class FooMiddleware extends Middleware
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

class FooApplication extends BaseApplication
{
}

/**
 *
 */
class MockMiddlewareRunner extends MiddlewareRunner
{
    public function run(Request $request, Response $response)
    {
    }
    public function getStack()
    {
        return $this->middlewareStack;
    }
}

class BaseApplicationTest extends \PHPUnit\Framework\TestCase
{
    public function testUnkownMiddleware()
    {
        $middlewareRunner = $this->createMock(MiddlewareRunner::class);
        $middlewareRunner->method('run')
            ->willReturn(null);

        $application = new FooApplication(new Request(), new Response(), $middlewareRunner);
        $this->expectException(InvalidArgumentException::class);
        $application->loadMiddleware('FormSecurity');
    }
    public function testLoadMiddleware()
    {
        $middlewareRunner = $this->createMock(MiddlewareRunner::class);
        $middlewareRunner->method('run')
            ->willReturn(null);

        $application = new FooApplication(new Request(), new Response(), $middlewareRunner);
        $this->assertNull($application->loadMiddleware(FooMiddleware::class));
    }
}
