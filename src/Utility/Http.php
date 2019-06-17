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

namespace Origin\Utility;

use Origin\Exception\HttpException;
use Origin\Core\ConfigTrait;
use Origin\Exception\NotFoundException;
use Origin\Utility\Http\Response;
use CURLFile;

class Http
{
    use ConfigTrait;

    /**
     * Holds the default configuration
     *
     *
     * @var array
     */
    protected $defaultConfig = [
        'timeout' => 30,
        'redirect' => true,
        'cookieJar' => true, // if this is set to true then cookies persisted for instance only
        'verbose' => false,
    ];

    /**
     * Persist cookies in instance
     *
     * @var array
     */
    protected $persistCookies = false;

    /**
     * Holds cookies to be persisted
     *
     * @var array
     */
    protected $cookies = [];
    /**
     * Constructor
     *
     * - base
     * - userAgent
     * - timeout
     * - cookieJar: file to save and read cookies from
     * - redirect: follow location headers
     * - type: json/xml
     * - auth (username, password, type)
     * - proxy (username, password,proxy)
     *
     * - referer
     * - curl: curl options
     * - query: appends query to field
     * - headers
     * - cookies array
     * - form (array of fields)
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config += ['cookieJar' => true];
        $this->config($config);
        if ($config['cookieJar'] === true) {
            $this->persistCookies = true;
        }
    }

    /**
     * Gets the cookies that are being persisted during
     * this instance
     *
     * @return array|null
     */
    public function cookies(string $name = null)
    {
        if ($name === null) {
            return $this->cookies;
        }

        if (isset($this->cookies[$name])) {
            return $this->cookies[$name];
        }

        return null;
    }

    /**
    * Sends a GET request
    *
    * @param string $url
    * @param array $options
    * @return \Origin\Utility\Http
    */
    public function get(string $url, array $options = [])
    {
        return $this->request('GET', $url, $options);
    }
    /**
      * Sends a HEAD request
      *
      * @param string $url
      * @return \Origin\Utility\Http
      */
    public function head(string $url, array $query = [], array $options = [])
    {
        return $this->request('HEAD', $url, $options);
    }

    /**
      * Sends a POST request
      *
      * @param string $url
      * @param array $data data to be posted
      * @param array $options
      * @return \Origin\Utility\Http
      */
    public function post(string $url, array $data = [], array $options = [])
    {
        return $this->request('POST', $url, $options += ['form' => $data]);
    }
    /**
      * Sends a PUT request
      *
      * @param string $url
      * @param array $data data to be posted
      * @param array $options
      * @return \Origin\Utility\Http
      */
    public function put(string $url, array $data =[], array $options = [])
    {
        return $this->request('PUT', $url, $options+= ['form' => $data]);
    }
    /**
      * Sends a PATCH request
      *
      * @param string $url
      * @param array $options
      * @return \Origin\Utility\Http
      */
    public function patch(string $url, array $options = [])
    {
        return $this->request('PATCH', $url, $options);
    }
    /**
     * Sends a DELETE request
     *
     * @param string $url
     * @param array $options
     * @return \Origin\Utility\Http
     */
    public function delete(string $url, array $options = [])
    {
        return $this->request('DELETE', $url, $options);
    }
    /**
      * Sends the actual request through cURL
      *
      * @param string $url
      * @param array $options
      * @return \Origin\Utility\Http
      */
    protected function send(array $options)
    {
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        if ($response === false) {
            $code = curl_errno($curl);
            $errorMessage = curl_error($curl);
            $status = ($code === CURLE_OPERATION_TIMEOUTED)?500:504; // error 500 or gateway timeout
            curl_close($curl);
            
            throw new HttpException("cURL Error: {$errorMessage}", $status);
        }

        # Process Curl Response
        $info = curl_getinfo($curl);
        $code = $info['http_code'];
      
        list($headers, $body) = $this->parseResponse($curl, $response);
       
        $cookies = $this->parseCookies($headers);
        $headers = $this->normalizeHeaders($headers);

        // Cookies
        curl_close($curl);

        # Create the Response Object
        $response = new Response();
        $response->body($body);
        $response->statusCode($code);
        foreach ($headers as $header => $value) {
            $response->header($header, $value);
        }
        
        foreach ($cookies as $name => $value) {
            $response->cookie($name, $value['value'], $value['expires'], $value);
            if ($this->persistCookies) {
                $this->cookies[$name] = $value;
            }
        }
        
        return $response;
    }

    /**
     * Undocumented function
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return \Origin\Utility\Http
     */
    public function request(string $method, string $url, array $options=[])
    {
        $options = $this->mergeOptions($options);
        $url = $this->buildUrl($url, $options);
        $options = $this->buildOptions(strtoupper($method), $url, $options);
        return $this->send($options);
    }

    /**
     * Returns a cURL file object
     * @return CURLFile
     */
    public static function file(string $filename)
    {
        if (!file_exists($filename)) {
            throw new NotFoundException("{$filename} could not be found");
        }
        $mime = mime_content_type($filename);
        $name = pathinfo($filename, PATHINFO_BASENAME);
        return new CURLFile($filename, $mime, $name);
    }

    protected function buildRequestHeaders(array $options)
    {
        // Process headers
        $headers = [];
        $cookies = [];
        if ($this->persistCookies) {
            $cookies = [];
            foreach ($this->cookies as $cookie) {
                $cookies[] = sprintf("%s=%s", rawurlencode($cookie['name']), rawurlencode($cookie['value']));
            }
        }
        if (!empty($options['cookies']) and is_array($options['cookies'])) {
            foreach ($options['cookies'] as $name => $value) {
                $cookies[] = sprintf("%s=%s", rawurlencode($name), rawurlencode($value));
                if ($this->persistCookies) {
                    $this->cookies[$name] = ['name'=>$name,'value'=>$value];
                }
            }
        }
        if ($cookies) {
            $headers[] = 'Cookie: ' . implode('; ', $cookies);
        }
     
        if (!empty($options['headers'])) {
            foreach ($options['headers'] as $header => $value) {
                if (is_int($header)) {
                    $headers[] = $value;
                } else {
                    $headers[] = "{$header}: {$value}";
                }
            }
        }
        if (!empty($options['type']) and in_array($options['type'], ['json','xml'])) {
            $headers[] = 'Accept: application/' . $options['type'];
            $headers[] = 'Content-Type: application/' . $options['type'];
        }

        return $headers;
    }

    protected function buildOptions(string $method, string $url, array $options)
    {
        $out = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $this->buildRequestHeaders($options),
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ];

    
        switch ($method) {
               case 'GET':
                   $out[CURLOPT_HTTPGET] = true;
               break;
               case 'HEAD':
                   $out[CURLOPT_NOBODY] = true;
               break;
               case 'POST':
                   $out[CURLOPT_POST] = true;
               break;
               default:
                   $out[CURLOPT_CUSTOMREQUEST] = $method;
               break;
           }

        if ($options['verbose']) {
            $out[CURLOPT_VERBOSE] = true;
        }
        if (!empty($options['userAgent'])) {
            $out[CURLOPT_USERAGENT] = $options['userAgent'];
        }

        if (!empty($options['referer'])) {
            $out[CURLOPT_REFERER] = $options['referer'];
        }

        if (!empty($options['redirect'])) {
            $out[CURLOPT_FOLLOWLOCATION] = $options['redirect'];
        }

        if (in_array($method, ['POST','PUT','PATCH'])) {
            if (!empty($options['form']) and is_array($options['form'])) {
                foreach ($options['form'] as $key => $value) {
                    if (is_string($value) and substr($value, 0, 1) === '@') {
                        $options['form'][$key] = Http::file(substr($value, 1));
                    }
                }
                if (!empty($options['type']) and $options['type']==='json') {
                    $out[CURLOPT_POSTFIELDS] = json_encode($options['form']);
                } else {
                    // Passing an array to CURLOPT_POSTFIELDS will encode the data as multipart/form-data,
                    // while passing a URL-encoded string will encode the data as application/x-www-form-urlencoded.
                    // Post not working on the other. Probably missing header?
                    $out[CURLOPT_POSTFIELDS] = http_build_query($options['form']);
                }
            }
        }

        if ($this->persistCookies === false and !empty($options['cookieJar'])) {
            $out[CURLOPT_COOKIEFILE] = $options['cookieJar'];
            $out[CURLOPT_COOKIEJAR] = $options['cookieJar'];
        }

        if (!empty($options['auth'])) {
            $options['auth'] += ['username'=>null,'password'=>null,'type'=>'basic'];
            $map = ['basic' => CURLAUTH_BASIC, 'digest' => CURLAUTH_DIGEST, 'ntlm' => CURLAUTH_NTLM,'any' => CURLAUTH_ANY];
            $out[CURLOPT_HTTPAUTH] = $map[$options['auth']['type']]??CURLAUTH_BASIC;
            $out[CURLOPT_USERPWD] = $options['auth']['username'] . ':' . $options['auth']['password'];
        }

        if (isset($options['proxy']['proxy'])) {
            $out[CURLOPT_PROXY] = $options['proxy']['proxy'];
            if (isset($options['proxy']['username'])) {
                $password = $options['proxy']['password']??'';
                $out[CURLOPT_PROXYUSERPWD] = $options['proxy']['username'] . ':' . $password;
            }
        }
       
        if (isset($options['curl']) and is_array($options['curl'])) {
            foreach ($options['curl'] as $key => $value) {
                if (is_string($key)) {
                    if (stripos($key, 'CURLOPT_') === false) {
                        $key = 'CURLOPT_' . $key;
                    }
                    $key = constant(strtoupper($key));
                }
                $out[$key] = $value;
            }
        }

        return $out;
    }


    protected function mergeOptions(array $options)
    {
        $options += $this->config();
        return $options;
    }

    protected function buildUrl(string $url, array $options)
    {
        if (!empty($options['base'])) {
            $url = $options['base'] . $url;
        }
        
        if (!empty($options['query']) and is_array($options['query'])) {
            $url .= '?' . http_build_query($options['query']);
        }
        return $url;
    }

    /**
      * Parses the response to get headers and body
      *
      * @param string $response
      * @return array
      */
    protected function parseResponse($ch, string $response)
    {
        // Parse Response
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerString = trim(substr($response, 0, $headerSize));
        $headers = explode("\r\n", $headerString);
        $body = substr($response, $headerSize);
        return [$headers,$body];
    }

    /**
     * Parses the cookies from headers.
     */
    protected function parseCookies(array &$headers)
    {
        $cookies = [];
        foreach ($headers as $i => $header) {
            if (substr($header, 0, 12) === 'Set-Cookie: ') {
                $cookie = $this->parseCookie($header);
                $cookies[$cookie['name']] = $cookie;
                unset($headers[$i]);
            }
        }
        return $cookies;
    }

    /**
     * Parses the value of a Set-Cookie: heder
     *
     * @param string $header line e.g. Set-Cookie: foo=bar;
     * @return array
     */
    protected function parseCookie(string $header) : array
    {
        list($void, $cookie) = explode('Set-Cookie: ', $header);
        $cookie = explode('; ', $cookie);
        list($name, $value) = explode('=', array_shift($cookie), 2);
        $out = ['name'=>$name,'value'=>$value,'expires'=>null,'path'=>null,'domain'=>null];
        foreach ($cookie as $attr) {
            if (strpos($attr, '=') !== false) {
                list($key, $v) = explode('=', $attr, 2);
                if ($key === 'expires') {
                    $v =strtotime($v);
                }
                $out[$key] = $v;
            } else {
                $out[] = $attr;
            }
        }
        return $out;
    }

    /**
     * Takes the curl headers and normalizes them
     *
     * @param array $headers
     * @return array $result
     */
    protected function normalizeHeaders(array $headers)
    {
        $result =[];
        foreach ($headers as $header) {
            if (strpos($header, ':') !== false) {
                list($header, $value) = explode(':', $header);
            } else {
                $value = null;
            }
            $result[$header] = $value;
        }
        return $result;
    }
}
