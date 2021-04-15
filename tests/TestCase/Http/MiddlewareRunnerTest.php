<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
use Origin\Http\Middleware\MiddlewareRunner;

class OneMiddleware extends Middleware
{
    public function handle(Request $request): void
    {
        $request->data('one', 'one');
    }
    public function process(Request $request, Response $response): void
    {
        $response->header('X-One', 'one');
    }
}

class TwoMiddleware extends Middleware
{
    public function handle(Request $request): void
    {
        $request->data('two', 'two');
    }

    public function process(Request $request, Response $response): void
    {
        $response->header('X-Two', 'two');
    }
}

class MiddlewareRunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testRun()
    {
        $request = new Request();
        $response = new Response();
        $runner = new MiddlewareRunner();
        $runner->add(new OneMiddleware());
        $runner->add(new TwoMiddleware());

        $runner->run($request, $response);

        $this->assertEquals('one', $request->data('one'));
        $this->assertEquals('two', $request->data('two'));
        $this->assertEquals('one', $response->headers('X-One'));
        $this->assertEquals('two', $response->headers('X-Two'));
    }
}
