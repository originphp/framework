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

use Origin\Http\Router;
use Origin\Http\Session;
use Origin\Http\Cookie;

use Origin\Exception\MethodNotAllowedException;

class Request
{
    /**
     * Request params.
     *
     * @var array
     */
    protected $params = array(
        'controller' => null,
        'action' => null,
        'args' => array(),
        'named' => array(),
        'plugin' => null,
        'route' => null
    );

    /**
     * Holds the query data.
     * @var array
     */
    protected $query = [];

    /**
     * Will contain form post data including from PUT/PATCH and delete
     *
     * @var array
     */
    protected $data = [];

    /**
     * Array of actual cookies
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Address of request including base folder WITHOUT Query params.
     *
     * @example /subfolder/controller/action
     */
    protected $url = null;

    /**
     * Original Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Mapped names
     *
     * @var array
     */
    protected $headersNames = [];

    /**
     * Session object
     *
     * @var \Origin\Http\Session
     */
    protected $session = null;

    /**
     * Holds the requested format. e.g html,xml,json
     *
     * @var string
     */
    protected $format = null;

    /**
     * This makes it easy for testing e.g  $request = new Request('articles/edit/2048');
     *
     * @param string $url articles/edit/2048
     */
    public function __construct($url = null)
    {
        $this->initialize($url);
    }

    /**
     * Initializes the request
     * @params string $url articles/edit/2048
     */
    public function initialize($url = null)
    {
        if ($url === null) {
            $url = $this->uri();
        }
        if (strlen($url) and $url[0] === '/') {
            $url = substr($url, 1);
        }

        $this->params = Router::parse($url);
       
        $this->processGet($url);
        $this->processPost();
        $this->processFiles();
        $this->processCookies();

        if (PHP_SAPI != 'cli') {
            $this->headers = getallheaders();
            foreach ($this->headers as $key => $value) {
                $this->headersNames[strtolower($key)] = $key;
            }
        }
        $this->detectRequestType();
        Router::request($this);
    }

    /**
     * Set/get the values in query
     *
     *  $all = $request-query();
     *  $value = $request->query('key');
     *  $request->query('key','value');
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function query(string $key = null, $value = null)
    {
        if (func_num_args() === 2) {
            $this->query[$key] = $value;
            return;
        }
        if ($key === null) {
            return $this->query;
        }
        if (isset($this->query[$key])) {
            return $this->query[$key];
        }
        return null;
    }


    /**
     * Set/get the values in data, can set individual or whole data array
     *
     *  $all = $request->data();
     *  $value = $request->data('key');
     *  $request->data('key','value');
     *  $request->data($someArray);
     *
     * @param string|array $key name of key to get,
     * @param mixed $value
     * @return mixed
     */
    public function data(string $key = null, $value = null)
    {
        if ($key === null) {
            return $this->data;
        }
 
        if (func_num_args() === 2) {
            return $this->data[$key] = $value;
        }
     
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Set/get the values in params
     *
     *  $all = $request->params();
     *  $value = $request->params('key');
     *  $request->params('key','value');
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function params(string $key = null, $value = null)
    {
        if (func_num_args() === 2) {
            $this->params[$key] = $value;
            return;
        }
        if ($key === null) {
            return $this->params;
        }
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        return null;
    }

    /**
     * Gets the URL for request
     * uri: /controller/action/100.
     *
     * @return string uri
     */
    protected function uri()
    {
        if ($uri = $this->env('REQUEST_URI')) {
            return $uri;
        }
        return '';
    }

    /**
     * This will return the url of the request without the query string unless you set includeQuery
     * to true.
     * @example /contacts/view/100
     * @param boolean $includeQuery (default:false) /contacts/view/100?page=1
     * @return string
     */
    public function url(bool $includeQuery = false)
    {
        $url = $this->url;
        if ($includeQuery and $this->query) {
            $url .= '?' . http_build_query($this->query);
        }
    
        return $url;
    }

    /**
     * Returns the referrer
     *
     * @return void
     */
    public function referer()
    {
        return $this->env('HTTP_REFERER'); // Misspelling is correct
    }

    /**
       * Sets and gets the type that should be rendered. html, json or xml
       *
       * @param string $type
       * @return string|null
       */
    public function type(string $type = null)
    {
        if ($type === null) {
            return $this->type;
        }
        $this->type = $type;
    }
  
    /**
     * This detects the type for the request.
     *
     * 1. If the route itself says it must be in a type, then use that first.
     * 2. If the client requests using an extension then it is assumed that is what is to be delivered.
     * 3. If the accept header is set as such
     *
     * This is all only relevant for autorendering.
     *
     * @return void
     */
    protected function detectRequestType()
    {
        $type = 'html';
        if ($this->params('type') and in_array($this->params('type'), ['xml','json'])) {
            $type = $this->params('type');
        }
        $extension = $this->params('ext');
        if ($extension and in_array($extension, ['html','json','xml'])) {
            $type = $extension;
        } else {
            $accepts = $this->accepts();
            if ($accepts) {
                if ($accepts[0] === 'application/json') {
                    $type = 'json';
                } elseif (in_array($accepts[0], ['application/xml','text/xml'])) {
                    $type =  'xml';
                }
            }
        }
        return $this->type($type);
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

    /**
     * curl -i -X POST -H 'Content-Type: application/json' -d '{"title":"CNBC","url":"https://www.cnbc.com"}' http://localhost:8000/bookmarks/add
     *
     * @return void
     */
    protected function processPost()
    {
        $data = (array) $_POST;
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
        }
        $this->data = $data;

        return $data;
    }

    /**
     * Process the files array
     *
     * @return void
     */
    protected function processFiles()
    {
        foreach ($_FILES as $name => $data) {
            $this->data[$name] = $data;
        }
    }

    /**
     * Checks the server request method.
     *
     * @param string|array $method get|post|put|delete
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

    /**
     * Returns the server request method
     * example get|post|put|delete
     * @return string
     */
    public function method()
    {
        return $this->env('REQUEST_METHOD');
    }

    /**
     * Run this from the controller to only allow certian methods, if the
     * method is not of a certain type e..g post/get/put then it will throw
     * and exception
     *
     * @param string|array $type e.g. post or get
     * @return bool
     */
    public function allowMethod($type)
    {
        if ($this->is($type)) {
            return true;
        }
        throw new MethodNotAllowedException();
    }
 
    /**
     * Checks if the request accepts, this will search the HTTP accept headers and if the
     * router has passed an ext parameter
     *
     * $request->accepts('application/json');
     * $request->accepts(['application/xml','application/json']);
     *
     * @param string|array $type
     * @return bool|array
     */
    public function accepts($type=null)
    {
        $path = parse_url($this->url(), PHP_URL_PATH);
    
        $acceptHeaders = $this->parseAcceptWith($this->headers('accept'));
        if ($type === null) {
            return $acceptHeaders;
        }
     
        // If router extension set and its valid.
        $extension = $this->params('ext');
        if ($extension and in_array($extension, ['html','json','xml'])) {
            return true;
        }
        
        foreach ((array) $type as $needle) {
            if (in_array($needle, $acceptHeaders)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Gets a list of accepted languages, checks if a specific language is accepted
     *
     * @param string $language
     * @return array|bool
     */
    public function acceptLanguage(string $language = null)
    {
        $acceptedLanguages = [];

        $languages = $this->parseAcceptWith($this->headers('accept-language'));
        foreach ($languages as $lang) {
            $acceptedLanguages[] = str_replace('-', '_', $lang);
        }
        
        if ($language === null) {
            return $acceptedLanguages;
        }
    
        return in_array($language, $acceptedLanguages);
    }

    /**
     * Parse accept headers into arrays
     * example: en-GB,en;q=0.9,es;q=0.8 becomes [en-GB,en,es]
     *
     * @param string $header
     * @return array
     */
    protected function parseAcceptWith(string $header = null) : array
    {
        $accepts = [];
        if ($header === null) {
            return [];
        }
        $values = explode(',', $header);
        foreach ($values as $value) {
            $value = trim($value);
            $pos = strpos($value, ';');
            if ($pos !== false) {
                $value = substr($value, 0, $pos);
            }
            $accepts[] = $value;
        }
        return $accepts;
    }

    /**
     * Gets an enviroment variable from $_SERVER.
     *
     * @param string $key
     * @return string|null
     */
    public function env(string $key) : ?string
    {
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return null;
    }

    /**
     * Sets or gets a header (you can get in psr friendly way, lowercase)
     *
     * $result= $request->header('www-Authenticate');
     * $request->header('WWW-Authenticate', 'Negotiate');
     *
     * @param string $name name of header to get
     * @param string $value value of header to set
     * @return string|null
     */
    public function header(string $name, string $value = null)
    {
        deprecationWarning('Request:header is depreciated use request:headers');
        if (func_num_args() === 1) {
            return $this->headers($name);
        }
        $normalized = strtolower($name); // psr thing
        $this->headers[$name] = $value;
        $this->headersNames[$normalized] = $name;
    }

    /**
     * Sets and gets headers

     * @param string $name
     * @param string $value
     * @return array|string|null
     */
    public function headers(string $name = null, string $value = null)
    {
        if ($name === null) {
            return $this->headers;
        }

        $normalized = strtolower($name); // psr thing

        if (func_num_args() === 1) {
            $key = $name;
            if (isset($this->headersNames[$normalized])) {
                $key = $this->headersNames[$normalized];
            }
            if (isset($this->headers[$key])) {
                return $this->headers[$key];
            }
            return null;
        }

        $this->headers[$name] = $value;
        $this->headersNames[$normalized] = $name;
    }

    /**
     * Returns the session object
     */
    public function session()
    {
        if ($this->session === null) {
            $this->session = new Session();
        }
        return $this->session;
    }

    /**
     * Reads a cookie value from the request. Cookies set
     * using the response::cookie would not of been sent yet.
     *
     * @return string|array|null
     */
    public function cookies(string $key = null, string $value = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
    
        if (func_num_args() === 1) {
            if (isset($this->cookies[$key])) {
                return $this->cookies[$key];
            }
            return null;
        }
        $this->cookies[$key] = $value;
    }
    
    protected function processCookies()
    {
        $cookie = new Cookie();
        $this->cookies = [];
        foreach (array_keys($_COOKIE) as $key) {
            $this->cookies[$key] = $cookie->read($key);
        }
    }
    /**
     * Reads the php://input stream
     *
     * @return string
     */
    protected function readInput() : ?string
    {
        $fh = fopen('php://input', 'r');
        $contents = stream_get_contents($fh);
        fclose($fh);
        return $contents;
    }
}
