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

/**
 * Web Application holder
 */

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Exception\Exception;
use Origin\Http\Middleware;
use Origin\Http\MiddlewareRunner;
use Origin\Core\Resolver;
use Origin\Exception\InvalidArgumentException;

class BaseApplication
{
    protected $runner = null;

    public function __construct(Request $request, Response $response, MiddlewareRunner $runner = null)
    {
        if ($runner === null) {
            $runner = new MiddlewareRunner();
        }
        $this->runner = $runner;

        $this->initialize();
        $this->loadMiddleware('Dispatcher'); # By running last it will run process/shutdown first
        $this->runner->run($request, $response);
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
     * @param \Origin\Middleware\Middleware $object
     * @return void
     */
    public function addMiddleware(Middleware $object)
    {
        $this->runner->add($object);
    }

    /**
     * Loads middleware using name, a class name
     *
     * Examples
     *
     * $this->loadMiddleware('FormSecurity');
     * $this->loadMiddleware('MyPlugin.FormSecurity');
     * $this->loadMiddleware('App\Middleware\FormSecurityMiddleware');
     *
     * @param string $name FormSecurity, MyPlugin.FormSecurity, App\Middleware\FormSecurityMiddleware
     * @return void
     */
    public function loadMiddleware(string $name)
    {
        $className = Resolver::className($name, 'Middleware', 'Middleware');
        if (empty($className)) {
            throw new InvalidArgumentException(sprintf("Unkown Middleware %s", $name));
        }
        $this->addMiddleware(new $className);
    }
}
