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

use Origin\Http\Cookie;
use Origin\Exception\NotFoundException;

class Response
{
    /**
     * Holds the buffered output.
     *
     * @var string
     */
    protected $body = null;

    /**
     * HTTP Status code to send.
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
        'txt' => 'text/plain',
    ];

    /**
     * The filename to send
     *
     * @var string
     */
    protected $file = null;

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
        if ($this->file) {
            // @codeCoverageIgnoreStart
            readfile($this->file);
        // @codeCoverageIgnoreEnd
        } else {
            echo $this->body;
        }
    }

    /**
     * Checks the status of the response object to see if its ready to be used.
     * If body has already been sent or a file set then its nos longer in ready state.
     * @return bool
     */
    public function ready() : bool
    {
        return empty($this->body) and empty($this->file);
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
     * @param int $status
     * @return int status
     */
    public function status(int $status = null)
    {
        deprecationWarning('status has been depreciated use statusCode instead.');
        return $this->statusCode($status);
    }

    /**
     * Sets or gets the HTTP status code for sending.
     *
     * @param int $code
     * @return int statusCode
     */
    public function statusCode(int $code = null)
    {
        if ($code === null) {
            return $this->statusCode;
        }
        $this->statusCode = $code;
    }

    /**
     * Sets a header. you can also pass an array of headers to set.
     *
     *  $response->header('HTTP/1.0 404 Not Found');
     *  $response->header('Accept-Language', 'en-us,en;q=0.5');
     *  $response->header(['Accept-Encoding'=>'gzip,deflate']);
     *
     * @param string|array $header []
     * @param mixed  $value
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
     * Gets all cookies or a single cookie that will be sent in this RESPONSE
     *
     * @return string|null|array cookies
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
        // @codeCoverageIgnoreStart
        header($header);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Sets a cookie in the RESPONSE (what is going
     * to be sent
     *
     *  $response->cookie('key',$value);
     *  $response->cookie('key',$value,'+5 days');
     *
     * @param string $name
     * @param mixed $value
     * @param string $expire a strtotime compatible string e.g. +5 days, 2019-01-01 10:23:55
     * @param array $options setcookie params: encrypt,path,domain,secure,httpOnly
     * @return mixed
     */
    public function cookie(string $name, $value, string $expire='+1 month', array $options = [])
    {
        $options += [
            'value' => $value,
            'path' => '/', // path on server
            'domain' => '', // domains cookie will be available on
            'secure' => false, // only send if through https
            'httpOnly' => false, // only available to  HTTP protocol not to javascript
            'expire' => strtotime($expire),
            'encrypt' => true
        ];
        
        $this->cookies[$name] = $options;
    }

    /**
     * Sets the content type
     *
     *  // get the current content type
     *  $contentType = $response->type();
     *
     *  // add definitions
     *  $response->type(['swf' => 'application/x-shockwave-flash']);
     *
     *
     *
     * @param string $contentType
     * @return void
     */
    public function type($contentType = null)
    {
        if ($contentType === null) {
            return $this->contentType;
        }
        if (is_array($contentType)) {
            foreach ($contentType as $type => $definition) {
                $this->mimeTypes[$type] = $definition;
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

    /**
     * Renders a file either for download or inline
     *
     * ## Options
     * - name: the filename to appear in browser
     * - download: if true
     * - type: mime content type (default autodetected )
     *
     * @param string $filename name and location of file
     * @param array $options (name,download)
     * @return void
     */
    public function file(string $filename, array $options=[])
    {
        # Setup Options
        $options += ['name'=>null,'download'=>false,'type'=>null];
        if (!file_exists($filename)) {
            throw new NotFoundException(sprintf('The requested file %s could not be found or read.', $filename));
        }
        if ($options['name']===null) {
            $options['name'] = basename($filename);
        }
        if ($options['type']=== null) {
            $options['type'] = mime_content_type($filename);
        }
        
        if ($options['download']) {
            $this->header('Content-Disposition', 'attachment; filename="' . $options['name'] . '"');
        }
        $this->type($options['type']);
      
        $this->file = $filename;
    }

    private function sendCookies()
    {
        $cookie = new Cookie();
        foreach ($this->cookies as $name => $options) {
            $cookie->write($name, $options['value'], $options['expire'], $options);
        }
    }
}
