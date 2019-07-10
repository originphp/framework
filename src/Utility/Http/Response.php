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
declare(strict_types=1);
namespace Origin\Utility\Http;

use Origin\Utility\Xml;

class Response
{
    /**
     * Response body
     *
     * @var string
     */
    protected $body = null;

    /**
     * Response Code
     *
     * @var int
     */
    protected $statusCode = null;

    /**
     * Response Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Response cookies
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Sets and gets the response code
     *
     * @param integer $code
     * @return int|void
     */
    public function statusCode(int $code = null)
    {
        if ($code === null) {
            return $this->statusCode;
        }
        $this->statusCode = $code;
    }

    /**
     * Sets or gets the body
     *
     * @param string $body
     * @return string|void
     */
    public function body(string $body = null)
    {
        if ($body === null) {
            return $this->body;
        }
        $this->body = $body;
    }

    /**
    * Gets all headers or a single header that will be sent
    *
    * @return string|null|array headers
    */
    public function headers(string $header = null)
    {
        if ($header === null) {
            return $this->headers;
        }

        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        }
        return null;
    }

    /**
     * Gets all cookies or single cookie
     *
     * @param string $cookie
     * @return array|null
     */
    public function cookies(string $cookie = null)
    {
        if ($cookie === null) {
            return $this->cookies;
        }
        if (isset($this->cookies[$cookie])) {
            return $this->cookies[$cookie];
        }
        return null;
    }

    /**
     * Sets a header
     *
     * @param string $header
     * @param string $value
     * @return void
     */
    public function header(string $header, string $value = null) :void
    {
        $this->headers[$header] = $value;
    }

    /**
     * Sets a cookie
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function cookie(string $name, $value, string $expire=null, array $options = []) :void
    {
        $options += [
            'name' => $name,
            'value' => $value,
            'path' => '/', // path on server
            'domain' => '', // domains cookie will be available on
            'secure' => false, // only send if through https
            'httpOnly' => false, // only available to  HTTP protocol not to javascript
            'expire' => $expire // convert from
        ];
        
        $this->cookies[$name] = $options;
    }

    
    /**
     * Gets the body as json
     *
     * @return array|null
     */
    public function json() : ?array
    {
        if ($this->body) {
            return json_decode($this->body, true);
        }
        return null;
    }

    /**
     * Gets the body as xml array
     *
     * @return array|null
     */
    public function xml() : ?array
    {
        if ($this->body) {
            return xml::toArray($this->body);
        }
        return null;
    }

    public function __toString()
    {
        return $this->body;
    }

    /**
     * Check if the response was SUCCESS
     *
     * @return boolean
     */
    public function success() : bool
    {
        $code = $this->statusCode();
        return in_array($code, [200,201,202,204]); // ok,created,accepted,no content
    }

    /**
     * Checks if response has a redirect status code
     *
     * @return boolean
     */
    public function redirect() : bool
    {
        $code = $this->statusCode();
        return in_array($code, [301,302,303,304,307]); // moved,found,see other,not modified, temp redirect
    }

    /**
    * Check if the response returned a 200 status code
    *
    * @return boolean
    */
    public function ok() : bool
    {
        return $this->statusCode() === 200;
    }
}
