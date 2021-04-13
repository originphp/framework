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

use function Origin\Defer\defer;
use Origin\Http\Exception\MethodNotAllowedException;

class Request
{
    /**
     * Request params.
     *
     * @var array
     */
    protected $params = [
        'controller' => null,
        'action' => null,
        'args' => [],
        'named' => [],
        'plugin' => null,
        'route' => null,
    ];

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
     * @example https://www.example.com/subfolder/controller/action
     * @var string
     */
    protected $url = null;

    /**
     * Path of request including base folder WITHOUT Query params.
     *
     * @example /subfolder/controller/action
     * @var string
     */
    protected $path = null;

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
     * Holds the environment vars
     *
     * @var array
     */
    protected $environment = null;

    /**
     * Request type
     *
     * @deprecated This will be deprecated
     *
     * @var string
     */
    protected $type = null;

    /**
     * This makes it easy for testing e.g $request = new Request('articles/edit/2048');
     *
     * @param string $url articles/edit/2048
     * @param array $options environment $_SERVER array
     * @return void
     */
    public function __construct(string $url = null, array $options = [])
    {
        $this->initialize($url, $options);
    }

    /**
     * Initializes the request
     *
     * @param string $url articles/edit/2048
     * @param array $options [$_SERVER,$_COOOKIE,$_POST,$_FILES]
     * @return void
     */
    public function initialize(string $url = null, array $options = []): void
    {
        $options += [
            'server' => $_SERVER,
            'post' => (array) $_POST,
            'cookie' => $this->processCookies(),
            'files' => (array) $_FILES,
        ];
        $this->environment = $options['server'];
        $this->cookies = $options['cookie'];

        if ($url === null) {
            $url = $this->uri();
        }
       
        if (strlen($url) && $url[0] === '/') {
            $url = substr($url, 1);
        }

        $this->processEnvironment($options['server']);
        $this->processGet($url);
        $this->processPost($options['post']);
        $this->processFiles($options['files']);
  
        Router::request($this);
        $this->params = Router::parse($url);
    }

    /**
     * Set/get the values in query
     *
     *  $all = $request-query();
     *  $value = $request->query('key');
     *  $request->query('key','value');
     *
     * @param string|array|null $key
     * @param mixed $value
     * @return mixed
     */
    public function query($key = null, $value = null)
    {
        if ($key === null) {
            return $this->query;
        }

        if (is_array($key)) {
            $this->query = $key;

            return;
        }
        if (func_num_args() === 2) {
            $this->query[$key] = $value;

            return;
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
     *  $request->data($someArray); // Will replace all data
     *
     * @param string|array|null $key
     * @param mixed $value
     * @return mixed
     */
    public function data($key = null, $value = null)
    {
        if ($key === null) {
            return $this->data;
        }

        if (is_array($key)) {
            $this->data = $key;

            return;
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
     * @param string|array|null $key
     * @param mixed $value
     * @return mixed
     */
    public function params($key = null, $value = null)
    {
        if ($key === null) {
            return $this->params;
        }

        if (is_array($key)) {
            $this->params = $key;

            return;
        }

        if (func_num_args() === 2) {
            $this->params[$key] = $value;

            return;
        }

        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        return null;
    }

    /**
     * Gets the URI for request
     * uri: /controller/action/100.
     *
     * @return string uri
     */
    protected function uri(): string
    {
        return $this->env('REQUEST_URI') ?? '';
    }

    /**
     * This will return the url of the request without the query string unless you set includeQuery
     * to true.
     *
     * @example /contacts/view/100
     * @param boolean $includeQuery (default:false) /contacts/view/100?page=1
     * @return string
     */
    public function url(bool $includeQuery = false): string
    {
        $url = $this->url;
        if ($includeQuery && $this->query) {
            $url .= '?' . http_build_query($this->query);
        }

        return $url;
    }

    /**
     * This will return the path of request without the query string unless you set includeQuery
     * to true.
     *
     * @example /contacts/view/100
     * @param boolean $includeQuery (default:false) /contacts/view/100?page=1
     * @return string
     */
    public function path(bool $includeQuery = false): string
    {
        $path = $this->path;
        if ($includeQuery && $this->query) {
            $path .= '?' . http_build_query($this->query);
        }

        return $path;
    }

    /**
     * Returns the referrer
     *
     * @return string|null
     */
    public function referer(): ?string
    {
        return $this->env('HTTP_REFERER'); // Misspelling is correct
    }

    /**
     * Sets and gets the request type (format) that will be rendered. If you want to know what
     * the request body format is then use contentType
     *
     * @deprecated this will be renamed to renderType or something similar to prevent confusion
     *
     * @param string $type
     * @return string|void
     */
    public function type(string $type = null)
    {
        deprecationWarning('Request::type has been deprecated set accept header instead.');
        if ($type === null) {
            return $this->type;
        }
        $this->type = $type;
    }

    /**
     * Detects the type to respond as e.g. html, json, xml
     *
     * 1. If in the routing params an extension has been set
     * 2. if the first available accept type is json or xml
     * 3. if no matches are found just return HTML
     *
     * @return string
     */
    public function respondAs(): string
    {
        /**
         * @backwards comptability
         */
        if ($this->type) {
            return $this->type;
        }

        $extension = $this->params('ext');
        if ($extension && in_array($extension, ['html', 'json', 'xml'])) {
            return $extension;
        }
       
        $accepts = $this->accepts();
        if ($accepts) {
            return $this->getType($accepts[0]);
        }
       
        return 'html';
    }

    /**
     * Checks if
     *
     * @param string $header
     * @return string
     */
    private function getType(string $header): string
    {
        if ($header === 'application/json') {
            return 'json';
        }
        if (in_array($header, ['application/xml', 'text/xml'])) {
            return 'xml';
        }

        return 'html';
    }

    /**
     * Checks if the request uses SSL
     * @deprecated
     * @return bool
     */
    public function ssl(): bool
    {
        deprecationWarning('Request ssl is deprecated use isSsl instead');

        return $this->isSsl();
    }

    /**
     * Checks if the request is ajax request
     * @deprecated
     * @return boolean
     */
    public function ajax(): bool
    {
        deprecationWarning('Request ajax is deprecated use isAjax instead');

        return $this->isAjax();
    }

    /**
     * Check to see whether the request was sent via SSL
     *
     * @return boolean
     */
    public function isSsl(): bool
    {
        return ($this->env('HTTPS') == 1 || $this->env('HTTPS') === 'on');
    }

    /**
     * Check to see whether the current request header X-Requested-With is equal to XMLHttpRequest.
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return ($this->env('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest');
    }

    /**
     * Check to see whether the request has a json extension or a accept ‘application/json’ mimetype.
     *
     * @return boolean
     */
    public function isJson(): bool
    {
        return $this->params('ext') === 'json' || $this->accepts('application/json');
    }

    /**
    * Check to see whether the request has a json extension or a accept ‘application/json’ mimetype.
    *
    * @return boolean
    */
    public function isXml(): bool
    {
        return $this->params('ext') === 'xml' || $this->accepts(['application/xml', 'text/xml']);
    }

    /**
     * Gets the ip address of the request
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        $ip = $this->env('HTTP_CLIENT_IP');
        if ($ip) {
            return $ip;
        }
        $ip = $this->env('HTTP_X_FORWARDED_FOR');
        if ($ip) {
            return $ip;
        }

        return $this->env('REMOTE_ADDR');
    }

    /**
     * Processes the GET stuff
     *
     * @param string $url
     * @return void
     */
    protected function processGet(string $url = null): void
    {
        // Build Query
        $query = [];
        if (strpos($url, '?') !== false) {
            list($url, $queryString) = explode('?', $url);
            parse_str($queryString, $query);
        }

        $this->path = '/' . $url;

        $host = $this->env('HTTP_HOST') ?? 'localhost';
        $scheme = $this->env('REQUEST_SCHEME') ?? 'http';
        $this->url = $scheme . '://' . $host . $this->path;

        $this->query($query);
    }

    /**
     * Gets the host name
     *
     * @return string|null
     */
    public function host(bool $trustProxy = false): ?string
    {
        return $trustProxy ? $this->env('HTTP_X_FORWARDED_HOST') : $this->env('HTTP_HOST');
    }

    /**
     * curl -i -X POST -H 'Content-Type: application/json' -d '{"title":"CNBC","url":"https://www.cnbc.com"}' http://localhost:8000/bookmarks/add
     *
     * @return array
     */
    protected function processPost(array $data = []): array
    {
        if ($this->is(['put', 'patch', 'delete'])) {
            parse_str($this->readInput(), $data);
        }
        if ($this->is(['post'])) {
            if ($this->env('CONTENT_TYPE') === 'application/json') {
                $input = $this->readInput();
                if ($input) {
                    $data = json_decode($this->readInput(), true);
                    if (! is_array($data)) {
                        $data = [];
                    }
                }
            }
        }
        $this->data($data);

        return $data;
    }

    /**
     * Process the files array
     *
     * @return void
     */
    protected function processFiles(array $files = []): void
    {
        foreach ($files as $header => $data) {
            $this->data[$header] = $data;
        }
    }

    /**
     * Checks the server request method.
     *
     * @param string|array $type get|post|put|delete
     * @return bool true or false
     */
    public function is($type): bool
    {
        $method = $this->env('REQUEST_METHOD');
        if (! $method) {
            return false;
        }
        if (! is_array($type)) {
            $type = [$type];
        }

        return in_array(strtolower($method), $type);
    }

    /**
     * Returns the server request method
     * example get|post|put|delete
     * @return string
     */
    public function method(): ?string
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
    public function allowMethod($type): bool
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
    public function accepts($type = null)
    {
        $acceptHeaders = $this->parseAcceptWith($this->headers('accept'));
        if ($type === null) {
            return $acceptHeaders;
        }

        // If router extension set and its valid.
        $extension = $this->params('ext');
        if ($extension && in_array($extension, ['html', 'json', 'xml'])) {
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
     * This is the content type that is used in this request
     *
     * @return string
     */
    public function contentType(): ?string
    {
        return $this->env('CONTENT_TYPE');
    }

    /**
     * Parse accept headers into arrays
     * example: en-GB,en;q=0.9,es;q=0.8 becomes [en-GB,en,es]
     *
     * @param string $header
     * @return array
     */
    protected function parseAcceptWith(string $header = null): array
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
     * Sets and gets an environment varabile for the request
     *
     * @param string $key
     * @param string $value
     * @return string|null|void
     */
    public function env(string $key, string $value = null)
    {
        if (func_num_args() === 2) {
            $this->environment[$key] = $value;

            return;
        }

        if (isset($this->environment[$key])) {
            return $this->environment[$key];
        }

        return null;
    }

    /**
     * Sets a request header (you can get in psr friendly way, lowercase)
     *
     * @see https://www.php-fig.org/psr/psr-7/
     *
     * $request->header('WWW-Authenticate', 'Negotiate');
     *
     * @param string $header name of header to get
     * @param string $value value of header to set
     * @return array|
     */
    public function header(string $header, string $value = null): array
    {
        // allow for HTTP/1.0 404 Not Found ? is this really needed
        if ($value === null && strpos($header, ':') != false) {
            list($header, $value) = explode(':', $header, 2);
        }
        $value = trim($value);
        $normalized = strtolower($header); // psr thing
       
        $this->headersNames[$normalized] = $header;
        $this->headers[$header] = $value;

        return [$header => $value];
    }

    /**
     * Gets headers
     *
     * @see https://www.php-fig.org/psr/psr-7/
     * @param string|array $header
     * @return mixed
     */
    public function headers($header = null)
    {
        if ($header === null) {
            return $this->headers;
        }
        if (is_array($header)) {
            $this->headers = $this->headersNames = [];
            foreach ($header as $key => $value) {
                $this->header($key, $value);
            }

            return $this->headers;
        }

        $normalized = strtolower($header); // psr thing
        $key = $this->headersNames[$normalized] ?? $header;

        return $this->headers[$key] ?? null;
    }

    /**
     * Returns the session object
     *
     * @return \Origin\Http\Session
     */
    public function session(): Session
    {
        if ($this->session === null) {
            $this->session = new Session();
        }

        return $this->session;
    }

    /**
     * Sets a value for a cookie on the request
     *
     * @param string $header
     * @param string $value
     * @return string
     */
    public function cookie(string $header, string $value): string
    {
        return $this->cookies[$header] = $value;
    }

    /**
     * Gets a cookie or sets the cookies array
     *
     *
     * @param string|array $key
     * @return mixed
     */
    public function cookies($key = null)
    {
        if ($key === null) {
            return $this->cookies;
        }

        if (is_array($key)) {
            return $this->cookies = $key;
        }

        return $this->cookies[$key] ?? null;
    }
    /**
     * Processes the $_COOKIE var
     *
     * @return array
     */
    protected function processCookies(): array
    {
        $cookie = new Cookie();
        $cookies = [];
        foreach (array_keys($_COOKIE) as $key) {
            $cookies[$key] = $cookie->read($key);
        }

        return $cookies;
    }

    /**
     * Processes the $_SERVER . PHP getallheaders() polyfill.
     *
     * @param array $environment
     * @return void
     */
    protected function processEnvironment(array $environment): void
    {
        foreach ($environment as $key => $value) {
            $header = null;
            if (strpos($key, 'HTTP_') !== false) {
                $header = substr($key, 5);
            }
            //CONTENT_TYPE,CONTENT_LENGTH,CONTENT_MD5
            if (strpos($key, 'CONTENT_') !== false) {
                $header = $key;
            }
            if ($header) {
                $header = str_replace('_', ' ', strtolower($header));
                $header = str_replace(' ', '-', ucwords($header));
                $this->header($header, $value);
            }
        }
    }

    /**
     * Reads the php://input stream
     *
     * @return string|false
     */
    protected function readInput()
    {
        $fh = fopen('php://input', 'r');
        defer($context, 'fclose', $fh);

        return stream_get_contents($fh);
    }
}
