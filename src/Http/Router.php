<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

/**
  * Rest routing
  * Router::add('/:controller', ['action'=>'index','method'=>'GET']);
  * Router::add('/:controller/*', ['action'=>'view','method'=>'GET']);
  * Router::add('/:controller', ['action'=>'add','method'=>'POST']);
  * Router::add('/:controller/*', ['action'=>'edit','method'=>'PUT']);
  * Router::add('/:controller/*', ['action'=>'edit','method'=>'PATCH']);
  * Router::add('/:controller/*', ['action'=>'delete','method'=>'DELETE']);
  */
use Origin\Inflector\Inflector;

class Router
{
    /**
     * @var array
     */
    protected static $routes = [];

    /**
     * @var \Origin\Http\Request
     */
    protected static $request = null;

    /**
     * Default extensions to parse. e.g json, xml
     *
     * @var array
     */
    protected static $extensions = ['json','xml'];

    /**
     * Sets and gets the extensions to parse
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
     * Adds a new route
     *
     * @param string $route  '/contacts/view'
     * @param array  $params The following options keys supported
     *  - controller: name of the controller to route to e.g. Articles
     *  - action: name of the action to call in the controller e.g. indesx
     *  - args: arguments to be passed
     *  Conditions:
     *  - method: filter condition for request method e.g. post
     *  - type: filter condition for request type e.g. json
     * @return void
     */
    public static function add(string $route, array $params = []): void
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

        $path = $params['plugin'] ?? $params['path'] ?? '/';
     
        self::$routes[$path][] = $params;
    }

    /**
     * Parses a URL and returns the routing params.
     *
     * @param string $url
     * @return array|null
     */
    public static function parse(string $url): ? array
    {
        if (strlen($url) && $url[0] === '/') {
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
 
        $matched = static::matchRoutes($url);
        if (! $matched) {
            return null;
        }

        $params = array_merge($template, $matched['routedParams']);
        foreach ($matched['matches'] as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        // gracefully handle invalid routes
        $params['controller'] = $params['controller'] ? Inflector::studlyCaps($params['controller']) : null;

        $named = [];
        // Parse Greedy results *
        if (! empty($params['greedy'])) {
            foreach (explode('/', $params['greedy']) as $paramater) {
                if (strpos($paramater, ':') !== false) {
                    list($key, $value) = explode(':', $paramater);
                    $named[$key] = urldecode($value);
                    continue;
                }
                $params['args'][] = $paramater;
            }
        }
        $params['named'] = $named;

        unset($params['greedy']);
      
        return $params;
    }

    /**
     * Matches a URL against a route
     *
     * @param string $url
     * @return array
     */
    private static function matchRoutes(string $url): array
    {
        /**
         * Get paths sorted by longest path first.
         * This has been introduced so that default routes don't clash with
         * additional routes when bootstraping (future version)
         */
        $paths = array_keys(self::$routes);
        rsort($paths);

        $request = static::request();

        if ($request) {
            $requestMethod = static::request()->method();
            $requestType = static::request()->contentType();
        }

        foreach ($paths as $path) {
            foreach (self::$routes[$path] as $routedParams) {
                if (preg_match($routedParams['pattern'], $url, $matches)) {
                    if ($request) {
                        if (! empty($routedParams['method']) && strtoupper($routedParams['method']) !== $requestMethod) {
                            continue;
                        }
                        if (! empty($routedParams['type']) && $routedParams['type'] !== $requestType) {
                            // handle application/json or json
                            if ($routedParams['type'] !== $requestType || strpos($requestType, '/', $routedParams['type']) === false) {
                                continue;
                            }
                        }
                    }

                    unset($routedParams['method'],$routedParams['pattern']);

                    return ['routedParams' => $routedParams,'matches' => $matches];
                }
            }
        }
    }

    /**
     * Converts a url array into a string;.
     *
     * @param array|string $url
     * @return string url
     */
    public static function url($url): string
    {
        if (is_string($url)) {
            return $url; // nothing to do
        }
        if (empty($url)) {
            return '/';
        }

        $requestParams = static::$request->params();

        $params = [
            'controller' => null,
            'action' => null,
            'plugin' => $requestParams['plugin'] ?? null,
            'prefix' => $requestParams['prefix'] ?? null
        ];
        $url = array_merge($params, $url);

        $output = '';
        $extension = null;

        if (static::$request) {
            $params = static::$request->params();
        }
        if ($url['plugin']) {
            $output .= '/' . Inflector::underscored($url['plugin']);
        } elseif (! empty($url['prefix'])) {
            $output .= '/' . $url['prefix'];
        }

        unset($url['plugin'],$url['prefix']);

        $controller = empty($url['controller']) ? $params['controller'] : $url['controller'];
        $action = empty($url['action']) ? $params['action'] : $url['action'];
        $output .= '/' . Inflector::underscored($controller) . '/' . $action;

        unset($url['controller'],$url['action'],$url['plugin']);

        $queryString = '';
        if (isset($url['?']) && is_array($url['?'])) {
            $queryString = '?' . http_build_query($url['?']);
        }

        if (isset($url['#']) && is_string($url['#'])) {
            $queryString .= '#' . $url['#'];
        }

        if (! empty($url['ext'])) {
            $extension = '.' . $url['ext'];
        }

        unset($url['ext'],$url['?'],$url['#']);

        $arguments = [];
        foreach ($url as $key => $value) {
            if (is_int($key)) {
                $arguments[] = $value;
                continue;
            }
            $arguments[] = $key . ':' . urlencode($value);
        }
        if ($arguments) {
            $output .= '/' . implode('/', $arguments);
        }

        return $output . $extension . $queryString;
    }

    /**
     * Sets or gets the request objects
     *
     * @param \Origin\Http\Request $request
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
    public static function routes(): array
    {
        return self::$routes;
    }
}
