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
namespace Origin;

/**
 * Web Application holder
 */

use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Exception\Exception;
use Origin\Middleware\Middleware;
use Origin\Core\Resolver;

class BaseApplication
{
    protected $middlewareStack = [];

    public function __construct(Request $request, Response $response)
    {
        $this->initialize();
        foreach ($this->middlewareStack as $callable) {
            $response = $callable->process($request, $response);
            if (! $response instanceof Response) {
                throw new Exception('Middleware did not return a response object');
            }
        }
        unset($this->middlewareStack);
    }

    /**
     * This is where middleware is setup
     */
    public function initialize()
    {
    }

    /**
     * Adds a middleware object to the queue
     *
     * $this->addMiddleware(new FormSecurity());
     *
     * @param Middleware $callable
     * @return void
     */
    public function addMiddleware(Middleware $object)
    {
        $this->middlewareStack[] = $object;
    }

    /**
     * Loads a class as middleware
     *
     * Examples
     *
     * $this->loadMiddleware('FormSecurity');
     * $this->loadMiddleware('MyPlugin.User');
     * $this->loadMiddleware('App\Middleware\FormSecurity');
     *
     * @param string $name
     * @return void
     */
    public function loadMiddleware(string $name)
    {
        $className = Resolver::className($name, 'Middleware', 'Middleware');
        return $this->addMiddleware(new $className);
    }
}
