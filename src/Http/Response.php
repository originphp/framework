<?php
declare(strict_types = 1);
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

use Origin\Http\Exception\NotFoundException;

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
     * @return string|void body
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
     *
     * @return void
     */
    public function send() : void
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
     *
     * @return bool
     */
    public function ready() : bool
    {
        return empty($this->body) and empty($this->file);
    }

    /**
     * Wrapper to help with testing
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function stop()
    {
        exit();
    }

    /**
     * Sets or gets the HTTP status code for sending.
     *
     * @param int $code
     * @return int|void statusCode
     */
    public function statusCode(int $code = null)
    {
        if ($code === null) {
            return $this->statusCode;
        }
        $this->statusCode = $code;
    }

    /**
     * Sets a response header
     *
     *  $response->header('HTTP/1.0 404 Not Found');
     *  $response->header('Accept-Language', 'en-us,en;q=0.5');
     *  $response->header(['Accept-Encoding'=>'gzip,deflate']);
     *
     * @param string|array $header []
     * @param mixed $value
     * @return array
     */
    public function header($header, $value = null) : array
    {
        if (is_string($header)) {
            if ($value === null and strpos($header, ':') !== false) {
                list($header, $value) = explode(':', $header, 2);
            }
            $header = [$header => $value];
        }
      
        foreach ($header as $key => &$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
           
            $this->headers[$key] = $value;
        }

        return $header;
    }

    /**
     * Gets all headers or a single header that will be sent
     *
     * @param string $header
     * @return mixed
     */
    public function headers($header = null)
    {
        if ($header === null) {
            return $this->headers;
        }

        if (is_array($header)) {
            return $this->headers = $header;
        }

        return $this->headers[$header] ?? null;
    }

    /**
     * Gets all cookies, a single cookie or sets all the cookies
     *
     * @param string|array $cookie name or array of cookies
     * @return array|null
     */
    public function cookies($cookie = null)
    {
        if ($cookie === null) {
            return $this->cookies;
        }
        if (is_array($cookie)) {
            return $this->cookie = $cookie;
        }

        return $this->cookies[$cookie] ?? null;
    }

    /**
     * Sends a header if not already sent.
     *
     * sendHeader("HTTP/1.0 404 Not Found")
     * sendHeader("Location","http://www.example.com/")
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    protected function sendHeader(string $name, $value = null) : void
    {
        $header = $name;
        if ($value) {
            $header = "{$name}: {$value}";
        }
     
        if (! headers_sent($file, $line)) {
            // @codeCoverageIgnoreStart
            header($header);
            // @codeCoverageIgnoreEnd
        }
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
     * @return void
     */
    public function cookie(string $name, $value, string $expire = '+1 month', array $options = []) : void
    {
        $options += [
            'name' => $name,
            'value' => $value,
            'path' => '/', // path on server
            'domain' => '', // domains cookie will be available on
            'secure' => false, // only send if through https
            'httpOnly' => false, // only available to  HTTP protocol not to javascript
            'expire' => strtotime($expire),
            'encrypt' => true,
        ];
        
        $this->cookies[$name] = $options;
    }

    /**
     * Sets or gets the content type. You can use
     *
     *  // get the current content type
     *  $contentType = $response->type();
     *
     *  // add definitions
     *  $response->type(['swf' => 'application/x-shockwave-flash']);
     *
     * @param string $contentType
     * @return mixed
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
    public function file(string $filename, array $options = []) : void
    {
        # Setup Options
        $options += ['name' => null,'download' => false,'type' => null];
       
        if ($options['name'] === null) {
            $options['name'] = basename($filename);
        }

        if (! file_exists($filename)) {
            throw new NotFoundException(sprintf('The requested file %s could not be found or read.', $options['name']));
        }

        if ($options['type'] === null) {
            $options['type'] = mime_content_type($filename);
        }
       
        if ($options['download']) {
            $this->header('Content-Disposition', 'attachment; filename="' . $options['name'] . '"');
        }
        $this->type($options['type']);
     
        $this->file = $filename;
    }

    /**
     * Write the cookies
     *
     * @return void
     */
    protected function sendCookies() : void
    {
        $cookie = new Cookie();
        foreach ($this->cookies as $name => $options) {
            // @codeCoverageIgnoreStart
            $cookie->write($name, $options['value'], $options['expire'], $options);
            // @codeCoverageIgnoreEnd
        }
    }
}
