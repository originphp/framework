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
namespace Origin\Http;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware;

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
     * Adds a middleware to the runner
     *
     * @param \Origin\Http\Middleware $object
     * @return void
     */
    public function add(Middleware $object)
    {
        $this->middlewareStack[] = $object;
    }

    /**
     * Runs the middleware
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response $response
     */
    public function run(Request $request, Response $response)
    {
        return $this->__invoke($request, $response);
    }

    /**
     * Magic Method
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return Mixed
     */
    public function __invoke(Request $request, Response $response)
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
