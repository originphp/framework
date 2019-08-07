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

use Origin\Core\Configure;
use Origin\Controller\Controller;
use Origin\Core\Exception\RouterException;
use Origin\Controller\Exception\MissingMethodException;
use Origin\Controller\Exception\PrivateMethodException;
use Origin\Controller\Exception\MissingControllerException;

class Dispatcher
{
    /**
     * Singleton Instance of the Dispatcher
     *
     * @var Dispatcher
     */
    protected static $instance = null;
    /**
     * Controller object
     *
     * @var Controller
     */
    protected $controller = null;

    /**
       * Returns a single instance of the object
       *
       * @return Dispatcher
       */
    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new Dispatcher();
        }

        return static::$instance;
    }

    /**
     * Starts the disatch process by creating the request and response objects
     *
     * @param string $url
     * @return Controller
     */
    public function start(string $url = null)
    {
        $response = $this->dispatch(new Request($url), new Response());
        $response->send();
    }

    protected function getClass(string $controller, string $plugin = null)
    {
        $namespace = Configure::read('App.namespace');
        if ($plugin) {
            $namespace = $plugin;
        }

        return $namespace.'\Controller\\'. $controller . 'Controller';
    }

    /**
     * This is the dispatch workhorse
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dispatch(Request $request, Response $response)
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
     * @param string $class    Controller name
     * @param object $request
     * @param object $response
     *
     * @return Controller
     */
    protected function buildController(string $class, Request $request, Response $response)
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
     * @return Controller
     */
    public function controller()
    {
        return $this->controller;
    }
}
