<?php
declare(strict_types = 1);
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

use Origin\Core\Resolver;
use Origin\Exception\InvalidArgumentException;
use Origin\Http\Middleware\DispatcherMiddleware;

class BaseApplication
{
    protected $runner = null;

    public function __construct(Request $request, Response $response, MiddlewareRunner $runner = null)
    {
        $this->runner = $runner ? $runner : new MiddlewareRunner();

        $this->initialize();
        $this->addMiddleware(new DispatcherMiddleware); # By running last it will run process/shutdown first
        $this->runner->run($request, $response);
    }

    /**
     * This is where middleware is setup
     */
    public function initialize() : void
    {
    }

    /**
     * Adds a middleware object to the queue
     *
     * $this->addMiddleware(new FormSecurity());
     *
     * @param \Origin\Http\Middleware\Middleware $object
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
     * $this->loadMiddleware('App\Http\Middleware\FormSecurityMiddleware');
     *
     * @param string $name FormSecurity, MyPlugin.FormSecurity, App\Http\Middleware\FormSecurityMiddleware
     * @return void
     */
    public function loadMiddleware(string $name)
    {
        $className = Resolver::className($name, 'Middleware', 'Middleware', 'Http');
        if (empty($className)) {
            throw new InvalidArgumentException(sprintf('Unkown Middleware %s', $name));
        }
        $this->addMiddleware(new $className);
    }
}
