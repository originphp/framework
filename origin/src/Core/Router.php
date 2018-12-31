<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

class Router
{
    /**
     * Holds the routes.
     *
     * @var array
     */
    public static $routes = array();

    protected static $request = null;

    /**
     * Creates a new route.
     *
     * @param string $route  '/contacts/view'
     * @param array  $params array(controller,action,arguments);
     */
    public static function add(string $route, array $params = [])
    {
        $defaults = array(
          'controller' => null,
          'action' => null,
        );

        // Create REGEX pattern

        // Escape forward slashes for ReGex
        $pattern = preg_replace('/\//', '\\/', trim($route, '/'));

        // Convert vars e.g. :controller :action
        $pattern = preg_replace('/\:([a-z]+)/', '(?P<\1>[^\/]+)', $pattern);

        // Enable greedy capture
        $pattern = str_replace('*', '?(?P<greedy>.*)', $pattern);

        // Convert passed arguments to array
        $pass = [];
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $pass[] = $value;
                unset($params[$key]);
            }
        }
        $params['pass'] = $pass;
        $params['route'] = $route;

        $params = array_merge($defaults, $params);

        self::$routes["/^{$pattern}$/i"] = $params;
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

        $params = array();
        // Remove query
        if (strpos($url, '?') !== false) {
            list($url, $queryString) = explode('?', $url);
            parse_str($queryString, $query);
        }

        $template = array(
          'controller' => null,
          'action' => null,
          'pass' => null,
          'named' => null,
          'route' => null,
          'plugin' => null,
        );

        foreach (self::$routes as $route => $routedParams) {
            if (preg_match($route, $url, $matches)) {
                $params = array_merge($template, $routedParams);

                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                $params['controller'] = Inflector::camelize($params['controller']);
                break;
            }
        }

        // No params no route
        if (!empty($params)) {
            // Parse Greedy results *
            if (isset($params['greedy']) and !empty($params['greedy'])) {
                $pass = $named = [];
                $parts = explode('/', $params['greedy']);

                foreach ($parts as $paramater) {
                    if (strpos($paramater, ':') != false) {
                        list($key, $value) = explode(':', $paramater);
                        $named[$key] = urldecode($value);
                    } else {
                        $pass[] = $paramater;
                    }
                }
                $params['pass'] = $pass;
                $params['named'] = $named;
            }
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
        $params = array(
        'controller' => null,
        'action' => null,
        'plugin' => null,
      );
        $url = array_merge($params, $url);

        $output = '';
        $here = $base = null;

        if (static::$request) {
            $params = static::$request->params;
            $here = static::$request->url;
            $base = static::$request->base;
        }
        if ($url['plugin']) {
            $output .= '/'.$url['plugin'];
        }

        $url['controller'] = (empty($url['controller']) ? $params['controller'] : $url['controller']);

        $output .= '/'.Inflector::underscore($url['controller']);

        $url['action'] = (empty($url['action']) ? $params['action'] : $url['action']);
        $output .= '/'.$url['action'];

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

    public static function setRequest(Request $request)
    {
        static::$request = $request;
    }
}
