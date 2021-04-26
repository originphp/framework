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
namespace Origin\Http;

use Origin\Core\Resolver;
use Origin\Core\HookTrait;
use Origin\Http\Middleware\Middleware;
use Origin\Http\Middleware\MiddlewareRunner;
use Origin\Http\Middleware\DispatcherMiddleware;
use Origin\Core\Exception\InvalidArgumentException;

class BaseApplication
{
    use HookTrait;
    
    /**
     * @var \Origin\Http\MiddlewareRunner $runner
     */
    protected $runner = null;

    /**
     * Holds the request object.
     *
     * @var \Origin\Http\Request
     */
    private $request = null;

    /**
     * Holds the response object.
     *
     * @var \Origin\Http\Response
     */
    private $response = null;

    /**
     * This is the constructor, here you can inject different
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @param \Origin\Http\MiddlewareRunner $runner
     */
    public function __construct(Request $request = null, Response $response = null, MiddlewareRunner $runner = null)
    {
        $this->request = $request ?: new Request();
        $this->response = $response ?: new Response();
        $this->runner = $runner ?: new MiddlewareRunner();

        // Set the Request
        Router::request($this->request);

        $this->executeHook('initialize');
    }

    /**
     * Dispatches the application
     *
     * @return \Origin\Http\Response
     */
    public function dispatch(): Response
    {
       
        # By running last it will run it first during process
        $this->addMiddleware(new DispatcherMiddleware);

        $this->executeHook('startup');
        $this->runner->run($this->request, $this->response);
        $this->executeHook('shutdown');

        $this->request->session()->close();
        
        return $this->response;
    }

    /**
     * Adds a middleware object to the queue
     *
     * $this->addMiddleware(new FormSecurityMiddleware());
     *
     * @param \Origin\Http\Middleware\Middleware $object
     * @return void
     */
    public function addMiddleware(Middleware $object): void
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
     * @param array $options array of options to be passed to middleware
     * @return void
     */
    public function loadMiddleware(string $name, array $options = []): void
    {
        $className = Resolver::className($name, 'Middleware', 'Middleware', 'Http');
        if (empty($className)) {
            throw new InvalidArgumentException(sprintf('Unkown Middleware %s', $name));
        }
        $this->addMiddleware(new $className($options));
    }
}
