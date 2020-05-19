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

namespace Origin\Test\Http;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\BaseApplication;
use Origin\Http\Middleware\Middleware;
use Origin\Http\Middleware\MiddlewareRunner;
use Origin\Core\Exception\InvalidArgumentException;

class FooMiddleware extends Middleware
{
    public function handle(Request $request): void
    {
        $request->data('foo', 'bar');
    }
    public function process(Request $request, Response $response): void
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
    public function run(Request $request, Response $response): Response
    {
        return $response;
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
            ->willReturn(new Response());

        $application = new FooApplication(new Request(), new Response(), $middlewareRunner);
        $this->expectException(InvalidArgumentException::class);
        $application->loadMiddleware('FormSecurity');
    }
    public function testLoadMiddleware()
    {
        $middlewareRunner = $this->createMock(MiddlewareRunner::class);
        $middlewareRunner->method('run')
            ->willReturn(new Response());

        $application = new FooApplication(new Request(), new Response(), $middlewareRunner);
        $this->assertNull($application->loadMiddleware(FooMiddleware::class));
    }
}
