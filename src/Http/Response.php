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

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
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

    protected $sent = false;

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
    public function send(): void
    {
        http_response_code($this->statusCode);

        $this->sendCookies();
        $this->header('Content-Type', $this->contentType);
    
        /**
         * By default Cache-Control is set to private for all requests unless response is set to cached
         */
        if (! isset($this->headers['Cache-Control'])) {
            $isCacheable = $this->headers('Expires') || $this->headers('Last-Modified') ;
            $cacheControl = $isCacheable ? 'private, must-revalidate' : 'no-cache, private';
            $this->header('Cache-Control', $cacheControl);
        }
       
        foreach ($this->headers as $name => $value) {
            $this->sendHeader($name, $value);
        }
        if ($this->file) {
            readfile($this->file);
        } else {
            echo $this->body;
        }

        $this->sent = true;
    }

    /**
     * Checks the status of the response object to see if its ready to be used.
     * If body has already been sent or a file set then its nos longer in ready state.
     * @deprecated use sent instead
     * @return bool
     */
    public function ready(): bool
    {
        deprecationWarning('Response:ready has been deprecated use response:sent instead');

        return empty($this->body) && empty($this->file);
    }

    /**
     * Checks if the response was sent
     *
     * @return boolean
     */
    public function sent(): bool
    {
        return $this->sent;
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
     *  $response->header(['Accept-Encoding' => 'gzip,deflate']);
     *
     * @param string|array $header []
     * @param string $value
     * @return array
     */
    public function header($header, string $value = null): array
    {
        if (is_string($header)) {
            if ($value === null && strpos($header, ':') !== false) {
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
     * @param string|array $header
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
            return $this->cookies = $cookie;
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
    protected function sendHeader(string $name, $value = null): void
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
     *  $response->cookie('key',$value,['expires'=>'+5 days');
     *
     * @param string $name
     * @param mixed $value string, array etc
     * @param array $options The options keys are:
     *   - expires: default:'+1 month'. a strtotime string e.g. +5 days, 2019-01-01 10:23:55
     *   - encrypt: default:true. encrypt value
     *   - path: default:'/' . Path on server
     *   - domain: domains cookie will be available on
     *   - secure: default:false. only send if through https
     *   - httpOnly: default:false. only available to HTTP protocol not to javascript
     * @return void
     */
    public function cookie(string $name, $value, array $options = []): void
    {
        $options += [
            'name' => $name,
            'value' => $value,
            'expires' => '+1 month',
            'path' => '/', // path on server
            'domain' => '', // domains cookie will be available on
            'secure' => false, // only send if through https
            'httpOnly' => false, // only available to  HTTP protocol not to javascript
            'encrypt' => true,
        ];
        $options['expires'] = strtotime($options['expires']);
        
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
     * @deprecated use contentType instead
     *
     * @param string|array $contentType
     * @return mixed
     */
    public function type($contentType = null)
    {
        deprecationWarning('Using response:type has been deprecated use contentType or mimeTypes');

        if ($contentType === null) {
            return $this->contentType;
        }

        if (is_array($contentType)) {
            $this->mimeTypes($contentType);

            return $this->contentType;
        }
        if (isset($this->mimeTypes[$contentType])) {
            return $this->contentType = $this->mimeTypes[$contentType];
        }

        if (strpos($contentType, '/') !== false) {
            return $this->contentType = $contentType;
        }

        throw new InvalidArgumentException('Invalid content type');
    }

    /**
     * Sets or gets the mime type definitions
     *
     * @param array $definitions Definitions to add
     * @return array
     */
    public function mimeTypes(array $definitions = null): array
    {
        if (is_null($definitions)) {
            $definitions = $this->mimeTypes;
        }

        return $this->mimeTypes = $definitions;
    }

    /**
     * Gets or sets a mime type in the definition
     *
     * @param string $type
     * @param string $mime
     * @return string
     */
    public function mimeType(string $type, string $mime = null): string
    {
        if ($mime === null) {
            if (isset($this->mimeTypes[$type])) {
                return $this->mimeTypes[$type];
            }
            throw new InvalidArgumentException('Invalid content type');
        }

        return $this->mimeTypes[$type] = $mime;
    }

    /**
     * Sets or gets the the response file
     *
     * ## Options
     * - name: the filename to appear in browser
     * - download: if true
     * - type: mime content type (default autodetected )
     *
     * @param string|null $filename name and location of file
     * @param array $options The following option keys are supported
     *  - name: the filename to appear in browser
     *  - download: if true
     *  - type: mime content type (default autodetected )
     * @return string|null
     */
    public function file(string $filename = null, array $options = []): ?string
    {
        if ($filename === null) {
            return $this->file;
        }

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
        $this->contentType($options['type']);
     
        return $this->file = $filename;
    }

    /**
     * Sets the Expires headers
     *
     * @param string $time
     * @return void
     */
    public function expires(string $time = 'now'): void
    {
        $expires = date('Y-m-d H:i:s', strtotime($time));
        $dateTime = ( new DateTime($expires))->setTimeZone(new DateTimeZone('UTC'));
        $this->header('Expires', $dateTime->format('D, j M Y H:i:s') . ' GMT');
    }

    /**
     * Sets or gets the content type
     *
     * @param string $type
     * @return string|null
     */
    public function contentType(string $type = null): ?string
    {
        if ($type === null) {
            return $this->contentType;
        }

        if (isset($this->mimeTypes[$type])) {
            return $this->contentType = $this->mimeTypes[$type];
        }

        if (strpos($type, '/') !== false) {
            return $this->contentType = $type;
        }

        throw new InvalidArgumentException('Invalid content type');
    }

    /**
     * Write the cookies
     *
     * @return void
     */
    protected function sendCookies(): void
    {
        $cookie = new Cookie();
        foreach ($this->cookies as $name => $options) {
            // @codeCoverageIgnoreStart
            $cookie->write($name, $options['value'], $options);
            // @codeCoverageIgnoreEnd
        }
    }
}
