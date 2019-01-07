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

namespace Origin\Controller;

use Origin\Core\Router;
use Origin\Exception\MethodNotAllowedException;

class Request
{
    /**
     * Request params.
     *
     * @var array
     */
    public $params = array(
        'controller' => null,
        'action' => null,
        'pass' => array(),
        'named' => array(),
        'plugin' => null,
        'route' => null
    );

    /**
     * Holds the query data.
     */
    public $query = array();

    /**
     * Will contain form post data.
     *
     * @var array
     */
    public $data = array();

    /**
     * Address of request including base folder without Query params.
     *
     * @example /subfolder/controller/action
     */
    public $url = null;

    /**
     * Base.
     *
     * @todo subfolder
     */
    public $base = null;

    /**
     * Takes a uri from request uri.
     *
     * @example controller/action (without /)
     */
    public function __construct($url = null)
    {
        if ($url === null) {
            $url = $this->url();
        }
        if (strlen($url) and $url[0] === '/') {
            $url = substr($url, 1);
        }

        $this->params = Router::parse($url);

        $this->processGet($url);
        $this->processPost();

        Router::setRequest($this);
    }

    /**
     * Gets the URL for request
     * uri: /controller/action/100.
     *
     * @return string uri
     */
    public function url()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return '';
        }

        return $_SERVER['REQUEST_URI'];
    }

    protected function processGet($url)
    {
        // Build Query
        $query = [];
        if (strpos($url, '?') !== false) {
            list($url, $queryString) = explode('?', $url);
            parse_str($queryString, $query);
        }

        $this->url = '/'.$url;
        $this->query = $query;
    }

    protected function processPost()
    {
        $data = [];
        if ($this->is(['put', 'patch', 'delete'])) {
            parse_str($this->readInput(), $data);
        }
        if ($this->is(['post'])) {
            if ($this->env('CONTENT_TYPE') === 'application/json') {
                $data = json_decode($this->readInput(), true);
                if (!is_array($data)) {
                    $data = [];
                }
            }
            if (!empty($_POST)) {
                $data = $_POST;
            }
        }
        $this->data = $data;

        return $data;
    }

    /**
     * Checks the server request method.
     *
     * @param string|array $type get|post|put|delete
     *
     * @return bool true or false
     */
    public function is($type)
    {
        $method = $this->env('REQUEST_METHOD');
        if (!$method) {
            return false;
        }
        if (!is_array($type)) {
            $type = [$type];
        }

        return in_array(strtolower($method), $type);
    }

    public function allowMethod($type)
    {
        if ($this->is($type)) {
            return true;
        }
        throw new MethodNotAllowedException();
    }

    /**
     * Gets en enviroment variable from $_SERVER.
     *
     * @param string $key
     *
     * @return
     */
    public function env(string $key)
    {
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return false;
    }

    protected function readInput()
    {
        $fh = fopen('php://input', 'r');
        $contents = stream_get_contents($fh);
        fclose($fh);

        return $contents;
    }
}
