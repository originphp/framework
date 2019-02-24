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

namespace Origin\Controller;

class Response
{
    /**
     * Holds the buffered output.
     *
     * @var string
     */
    protected $body = null;

    /**
     * Status code to send.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * holds an array of headers to be sent.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * holds an array of cookies to be sent
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Holds the content type
     *
     * @var string
     */
    protected $contentType = 'text/html';

    protected $mimeTypes = [
        'html' => 'text/html',
        'json' => 'application/json',
        'xml' => 'application/xml',
    ];
    /**
     * Sets or gets the buffered output.
     *
     * @param string $content
     *
     * @return string body
     */
    public function body(string $content = null)
    {
        if ($content === null) {
            return $this->body;
        }

        $this->body = $content;
    }

    /**
     * Sets the headers and sends the response.
     */
    public function send()
    {
        http_response_code($this->statusCode);

        $this->sendCookies();
        $this->header('Content-Type', $this->contentType);

        foreach ($this->headers as $name => $value) {
            $this->sendHeader($name, $value);
        }
        echo $this->body;
    }

    /**
     * Wrapper for exit. Mocked during testing.
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function stop()
    {
        exit();
    }

    /**
     * Sets or gets the status code for sending.
     *
     * @param int $statusCode
     *
     * @return int statusCode
     */
    public function statusCode(int $statusCode = null)
    {
        if ($statusCode === null) {
            return $this->statusCode;
        }
        $this->statusCode = $statusCode;
    }

    /**
     * Sets a header
     *
     *  $response->header('Accept-Language', 'en-us,en;q=0.5');
     *  $response->header(['Accept-Encoding'=>'gzip,deflate']);
     *
     * @param string|array $header []
     * @param mixed  $value
     *
     * @return bool
     */
    public function header($header, $value = null)
    {
        if (is_string($header)) {
            $header = [$header=>$value];
        }
      
        foreach ($header as $key => $value) {
            $this->headers[$key] = $value;
        }

        return true;
    }

    /**
     * Gets the headers
     *
     * @return array headers
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Gets the cookies to be sent
     *
     * @return array cookies
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Sends a header if not already sent.
     *
     * sendHeader("HTTP/1.0 404 Not Found")
     * sendHeader("Location","http://www.example.com/")
     *
     * @param string $name
     * @param string $value
     */
    private function sendHeader(string $name, $value = null)
    {
        // don't try to send headers if already sent!
        if (headers_sent($file, $line)) {
            return;
        }
        $header = $name;
        if ($value) {
            $header = "{$name}: {$value}";
        }
        header($header);
    }


    /**
     * Sets a cookie or gets a cookie value from RESPONSE
     *
     *  $response->cookie('fruit','apple');
     *  $response->cookie('fruit,[
     *      'value' => 'apple',
     *      'expire' => strtotime('+1 day')
     *  ])
     *  $value = $response->cookie('fruit');
     *
     * @param string $name
     * @param array|null $value keys include value,path,domain,httpOnly,secure and expire
     * @return void
     */
    public function cookie(string $name, $options = null)
    {
        if ($options === null) {
            if (isset($this->cookies[$name])) {
                return $this->cookies[$name]['value'];
            }
            return false;
        }
        $defaults = ['value'=>null,'path'=>'/','domain'=>'','httpOnly'=>false,'secure'=>false,'expire'=>0];

        if (is_string($options)) {
            $options = ['value' => $options];
        }

        $options = array_merge($defaults, $options);
        $this->cookies[$name] = $options;
    }

    /**
     * Sets the content type
     *
     *  // get the current content type
     *  $contentType = $request->type();
     *
     *  // add definitions
     *  $response->type(['swf' => 'application/x-shockwave-flash']);
     *
     *
     *
     * @param string$contentType
     * @return void
     */
    public function type($contentType = null)
    {
        if ($contentType === null) {
            return $this->contentType;
        }
        if (is_array($contentType)) {
            foreach ($contentType as $type => $defintion) {
                $this->mimeTypes[$type] = $defintion;
            }
            return $this->contentType;
        }
        if (isset($this->mimeTypes[$contentType])) {
            return $this->contentType = $this->mimeTypes[$contentType];
        }

        if (strpos($contentType, '/') !== false) {
            return $this->contentType = $contentType;
        }

        return false;
    }

    private function sendCookies()
    {
        foreach ($this->cookies as $name => $options) {
            setcookie($name, $options['value'], $options['expire'], $options['path'], $options['domain'], $options['secure'], $options['httpOnly']);
        }
    }
}
