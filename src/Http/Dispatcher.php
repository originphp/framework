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

use Origin\Core\Config;
use Origin\Http\Controller\Controller;
use Origin\Core\Exception\RouterException;
use Origin\Http\Controller\Exception\MissingMethodException;
use Origin\Http\Controller\Exception\PrivateMethodException;
use Origin\Http\Controller\Exception\MissingControllerException;

class Dispatcher
{
    /**
     * Singleton Instance of the Dispatcher
     *
     * @var \Origin\Http\Dispatcher
     */
    protected static $instance = null;

    /**
     * Controller object
     *
     * @var \Origin\Http\Controller\Controller
     */
    protected $controller = null;

    /**
       * Returns a single instance of the object
       *
       * @return \Origin\Http\Dispatcher
       */
    public static function instance() : Dispatcher
    {
        if (static::$instance === null) {
            static::$instance = new Dispatcher();
        }

        return static::$instance;
    }

    /**
     * Determines the class name
     *
     * @param string $controller
     * @param string $plugin
     * @return string
     */
    protected function getClass(string $controller, string $plugin = null) : string
    {
        $namespace = Config::read('App.namespace');
        if ($plugin) {
            $namespace = $plugin;
        }

        return $namespace . '\Http\Controller\\' . $controller . 'Controller';
    }

    /**
     * This is the dispatch workhorse
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response
     */
    public function dispatch(Request $request, Response $response) : Response
    {
        if ($request->params('controller')) {
            $class = $this->getClass($request->params('controller'), $request->params('plugin'));
            if (! class_exists($class)) {
                throw new MissingControllerException($request->params('controller'));
            }
            
            $this->controller = $this->buildController($class, $request, $response);
          
            $this->invoke($this->controller, $request->params('action'), $request->params());

            return $this->controller->response;
        }
        throw new RouterException('No route found.', 404);
    }

    /**
     * Creates and returns the controller for the request.
     *
     * @param string $class Controller name
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Controller\Controller
     */
    protected function buildController(string $class, Request $request, Response $response) : Controller
    {
        $controller = new $class($request, $response);
        $action = $request->params('action');
        if (! method_exists($controller, $action)) {
            throw new MissingMethodException([$controller->name, $action]);
        }

        if (! $controller->isAccessible($action)) {
            throw new PrivateMethodException([$controller->name, $action]);
        }

        return $controller;
    }

    /**
     * Does the whole lifecylce
     */
    /**
     * Undocumented function
     *
     * @param \Origin\Http\Controller\Controller $controller
     * @param string $action
     * @param array $arguments
     * @return \Origin\Http\Response|void
     */
    protected function invoke(Controller $controller, string $action, array $arguments)
    {
        $result = $controller->startupProcess();
        if ($result instanceof Response or $controller->response->headers('Location')) {
            return $result;
        }

        call_user_func_array([$controller, $action], $arguments['args']);
     
        if ($controller->autoRender and $controller->response->ready()) {
            $controller->render();
        }

        return $controller->shutdownProcess();
    }

    /**
     * Gets the controller
     *
     * @return \Origin\Http\Controller\Controller
     */
    public function controller() : Controller
    {
        return $this->controller;
    }
}
