<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Core;

use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Controller\Controller;
use Origin\Controller\Exception\MissingControllerException;
use Origin\Controller\Exception\MissingMethodException;
use Origin\Controller\Exception\PrivateMethodException;
use Origin\Core\Exception\RouterException;

class Dispatcher
{
    protected $controller = null;

    public function start(string $url = null)
    {
        $Request = new Request($url);
        $Response = new Response();

        return $this->dispatch($Request, $Response);
    }

    public function dispatch(Request $request, Response $response, $output = true)
    {
        if ($request->params) {
            $class = $request->params['controller'].'Controller';

            $namespace = Configure::read('App.namespace');
            $class = $namespace.'\Controller\\'.$class;

            if ($request->params['plugin']) {
                $class = $request->params['plugin'].'\Controller\\'.$class;
            }

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
    public function buildController(string $class, Request $request, Response $response)
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

    public function invoke(Controller &$controller, string $action, array $arguments)
    {
        $response = null;

        $controller->startupProcess();

        $result = call_user_func_array(array($controller, $action), $arguments['pass']);

        if ($result === null and $controller->autoRender) {
            $controller->render();
        }

        $controller->shutdownProcess();

        return $controller;
    }

    /**
     * Gets the last request object.
     */
    public function getRequest()
    {
        return $this->controller->request;
    }

    public function getController()
    {
        return $this->controller;
    }
}
