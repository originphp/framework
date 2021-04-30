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
declare(strict_types = 1);
namespace Origin\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;

class MiddlewareRunner
{
    /**
     * Holds the middleware stack
     *
     * @var array
     */
    protected $middlewareStack = [];
    /**
     * Holds the current index marker
     *
     * @var integer
     */
    protected $current = 0;

    /**
     * Adds a Middleware to the end of the queue
     *
     * @param \Origin\Http\Middleware\Middleware $object
     * @return void
     */
    public function add(Middleware $object): void
    {
        $this->middlewareStack[] = $object;
    }

    /**
     * Adds a Middleware to the start of the queue
     *
     * @param Middleware $object
     * @return void
     */
    public function prepend(Middleware $object): void
    {
        array_unshift($this->middlewareStack, $object);
    }

    /**
     * Returns a list of the middleware added to the runner
     * @return array
     */
    public function list(): array
    {
        $out = [];
        foreach ($this->middlewareStack as $object) {
            $out[] = get_class($object);
        }

        return $out;
    }

    /**
     * Runs the middleware
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response $response
     */
    public function run(Request $request, Response $response): Response
    {
        return $this->__invoke($request, $response);
    }
    /**
     * Magic Method
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response $response
     */
    public function __invoke(Request $request, Response $response): Response
    {
        if (isset($this->middlewareStack[$this->current])) {
            $next = $this->middlewareStack[$this->current];
            if ($next) {
                $this->current ++;

                return $next($request, $response, $this);
            }
        }

        return $response;
    }
}
