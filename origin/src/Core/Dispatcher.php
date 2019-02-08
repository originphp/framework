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

namespace Origin\Core;

use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Controller\Controller;
use Origin\Controller\Exception\MissingControllerException;
use Origin\Controller\Exception\MissingMethodException;
use Origin\Controller\Exception\PrivateMethodException;
use Origin\Core\Exception\RouterException;
use Origin\Core\Configure;

class Dispatcher
{
    protected $controller = null;

    /**
     * Starts the disatch process by creating the request and response objects
     *
     * @param string $url
     * @return void
     */
    public function start(string $url = null)
    {
        $Request = new Request($url);
        $Response = new Response();

        return $this->dispatch($Request, $Response);
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
     * @return Controller
     */
    public function dispatch(Request $request, Response $response)
    {
        if ($request->params) {
            $class = $this->getClass($request->params['controller'], $request->params['plugin']);

            if (!class_exists($class)) {
                throw new MissingControllerException($request->params['controller']);
            }
            
            $this->controller = $this->buildController($class, $request, $response);
          
            $this->invoke(
              $this->controller,
              $request->params['action'],
              $request->params
            );
      
            if ($this->controller->response instanceof Response) {
                $this->controller->response->send();
            }
          
            return $this->controller;
        } else {
            throw new RouterException('No route found.', 404);
        }
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

        if (!method_exists($controller, $request->params['action'])) {
            throw new MissingMethodException([$controller->name, $request->params['action']]);
        }

        if (!$controller->isAccessible($request->params['action'])) {
            throw new PrivateMethodException([$controller->name, $request->params['action']]);
        }

        return $controller;
    }

    protected function invoke(Controller &$controller, string $action, array $arguments)
    {
        $response = null;
       
        $controller->startupProcess();
       
        $result = call_user_func_array(array($controller, $action), $arguments['pass']);
     
        if ($controller->autoRender) {
            $controller->render();
        }

        $controller->shutdownProcess();

        return $controller;
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
