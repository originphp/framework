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
        http_response_code($this->statusCode); /* @requires php 5.4 */
        foreach ($this->headers as $name => $value) {
            $this->sendHeader($name, $value);
        }
        echo $this->body;
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
     * Sets a header for sending.
     *
     * @param string $header
     * @param mixed  $value
     *
     * @return bool
     */
    public function header($header = null, $value = null)
    {
        if ($header == null) {
            return false;
        }
        if (is_string($header) and $value) {
            $this->headers[$header] = $value;

            return true;
        }
        foreach ($header as $key => $value) {
            $this->headers[$key] = $value;
        }

        return true;
    }

    /**
     * Returns the headers current set.
     *
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Sends a header if not already sent.
     *
     * sendHeader("HTTP/1.0 404 Not Found")
     * senderHeader("Location","http://www.example.com/")
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
        if ($value === null) {
            header($name);
        } else {
            header("{$name}: {$value}");
        }
    }
}
