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

 /**
  * Rest routing
  * Router::add('/:controller', ['action'=>'index','method'=>'GET']);
  * Router::add('/:controller/*', ['action'=>'view','method'=>'GET']);
  * Router::add('/:controller', ['action'=>'add','method'=>'POST']);
  * Router::add('/:controller/*', ['action'=>'edit','method'=>'PUT']);
  * Router::add('/:controller/*', ['action'=>'edit','method'=>'PATCH']);
  * Router::add('/:controller/*', ['action'=>'delete','method'=>'DELETE']);
  */

namespace Origin\Http;

use Origin\Utility\Inflector;
use Origin\Http\Request;

class Router
{
    /**
     * Holds the routes.
     *
     * @var array
     */
    protected static $routes = [];

    protected static $request = null;

    /**
     * Holds the default extensions to parse. e.g json, xml
     *
     * @var array
     */
    protected static $extensions = ['json','xml'];

    /**
     * Sets the extensions to parse
     *
     * @param array $extensions
     * @return array|void
     */
    public static function extensions(array $extensions = null)
    {
        if ($extensions === null) {
            return self::$extensions;
        }
        self::$extensions = $extensions;
    }

    /**
     * Creates a new route.
     *
     * @param string $route  '/contacts/view'
     * @param array  $params array(controller,action,arguments);
     */
    public static function add(string $route, array $params = [])
    {
        $defaults = [
            'controller' => null,
            'action' => null,
            'args' => null,
            'route' => null,
            'method' => null,
        ];

        // Create REGEX pattern

        // Escape forward slashes for ReGex
        $pattern = preg_replace('/\//', '\\/', trim($route, '/'));

        // Convert vars e.g. :controller :action
        $pattern = preg_replace('/\:([a-z]+)/', '(?P<\1>[^\.\/]+)', $pattern);

        // Enable greedy capture
        $pattern = str_replace('*', '?(\/(?P<greedy>.*))?', $pattern); //?(?P<greedy>.*)

        // Convert passed arguments to array
        $args = [];
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $args[] = $value;
                unset($params[$key]);
            }
        }
        $params['args'] = $args;
        $params['route'] = $route;

        $params = array_merge($defaults, $params);
        $params['pattern'] = "/^{$pattern}$/i";
        self::$routes[] = $params;
    }

    /**
     * Parses a URL and returns the routing params.
     *
     * @param string $url string
     *
     * @return array $params
     */
    public static function parse(string $url)
    {
        if (strlen($url) and $url[0] == '/') {
            $url = substr($url, 1);
        }

        $params = [];
        // Remove query
        if (strpos($url, '?') !== false) {
            list($url, $queryString) = explode('?', $url);
            parse_str($queryString, $query);
        }

        $template = [
            'controller' => null,
            'action' => null,
            'args' => [],
            'named' => [],
            'route' => null,
            'plugin' => null,
        ];

        // Remove matching extensions, so it can be parsed
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if (in_array($extension, self::$extensions)) {
            $template['ext'] = $extension;
            $length = strlen($template['ext']) + 1;
            $url = substr($url, 0, -$length);
        }
 
        foreach (self::$routes as $routedParams) {
            if (preg_match($routedParams['pattern'], $url, $matches)) {
                if (empty($routedParams['method']) or ($routedParams['method'] and strtoupper($routedParams['method']) === env('REQUEST_METHOD'))) {
                    unset($routedParams['method'],$routedParams['pattern']);
                    $params = array_merge($template, $routedParams);
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    // gracefully handle invalid routes
                    $params['controller'] = $params['controller'] ? Inflector::studlyCaps($params['controller']): null;
                    break;
                }
            }
        }

        // No params no route
        if (! empty($params)) {
            $named = [];
            // Parse Greedy results *
            if (! empty($params['greedy'])) {
                $parts = explode('/', $params['greedy']);
                foreach ($parts as $paramater) {
                    if (strpos($paramater, ':') != false) {
                        list($key, $value) = explode(':', $paramater);
                        $named[$key] = urldecode($value);
                    } else {
                        $params['args'][] = $paramater;
                    }
                }
            }
            $params['named'] = $named;
            unset($params['greedy']);
        }

        return $params;
    }

    /**
     * Converts a url array into a string;.
     *
     * @param array|string $url
     *
     * @return string url
     */
    public static function url($url)
    {
        if (is_string($url)) {
            return $url; // nothing to do
        }
        if (empty($url)) {
            return '/';
        }
        $params = [
            'controller' => null,
            'action' => null,
            'plugin' => null,
        ];
        $url = array_merge($params, $url);

        $output = '';

        if (static::$request) {
            $params = static::$request->params();
        }
        if ($url['plugin']) {
            $output .= '/' . Inflector::underscored($url['plugin']);
        }

        $controller = empty($url['controller']) ? $params['controller'] : $url['controller'];
        $action = empty($url['action']) ? $params['action'] : $url['action'];
        $output .= '/' . Inflector::underscored($controller) . '/' . $action;

        unset($url['controller'],$url['action'],$url['plugin']);

        $queryString = '';
        if (isset($url['?']) and is_array($url['?'])) {
            $queryString = '?'.http_build_query($url['?']);
            unset($url['?']);
        }

        if (isset($url['#']) and is_string($url['#'])) {
            $queryString .= '#'.$url['#'];
            unset($url['#']);
        }

        $arguments = [];
        foreach ($url as $key => $value) {
            if (is_int($key)) {
                $arguments[] = $value;
                continue;
            }
            $arguments[] = $key.':'.urlencode($value);
        }
        if ($arguments) {
            $output .= '/'.implode('/', $arguments);
        }

        return $output.$queryString;
    }

    /**
     * Sets or gets the request objects
     *
     * @param Request $request
     * @return \Origin\Http\Request|null
     */
    public static function request(Request $request = null)
    {
        if ($request === null) {
            return static::$request;
        }
    
        static::$request = $request;
    }

    /**
     * Gets the routes
     *
     * @return array
     */
    public static function routes()
    {
        return self::$routes;
    }
}
