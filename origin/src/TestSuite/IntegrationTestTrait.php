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

namespace Origin\TestSuite;

use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Core\Dispatcher;
use Origin\Core\Router;
use Origin\Core\Session;

/**
 * A way to test controllers from a higher level
 */

trait IntegrationTestTrait
{
    /**
     * Holds the response object
     *
     * @var Response
     */
    protected $response = null;
    /**
     * Holds the response object
     *
     * @var Request
     */
    protected $request = null;
    /**
     * Holds the controller for the most recent request
     *
     * @var Controller
     */
    protected $controller = null;

    /**
     * Holds session data for next request
     *
     * @var array
     */
    protected $session = [];
    /**
     * Holds cookies data for next request
     *
     * @var array
     */
    protected $cookies = [];
    /**
     * Holds headers data for next request
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Sets env vars for next request
     *
     * @var array
     */
    protected $env = [];
    /**
     * Sends a GET request
     *
     * @param string $url
     * @return void
     */
    public function get(string $url)
    {
        $this->sendRequest($url, 'GET');
    }
    /**
     * Sends a post request
     *
     * @param string $url
     * @param array $data
     * @return void
     */
    public function post(string $url, array $data = [])
    {
        $this->sendRequest($url, 'POST', $data);
    }
    
    /**
     * Sends a DELETE request
     *
     * @param string $url
     * @return void
     */
    public function delete(string $url)
    {
        $this->sendRequest($url, 'DELETE');
    }
    /**
     * Sends a PATCH request
     *
     * @param string $url
     * @param array $data
     * @return void
     */
    public function patch(string $url, array $data = [])
    {
        $this->sendRequest($url, 'PATCH', $data);
    }
  
    /**
     * Sends a PUT request
     *
     * @param string $url
     * @param array $data
     * @return void
     */
    public function put(string $url, array $data = [])
    {
        $this->sendRequest($url, 'PUT', $data);
    }
    
    /**
     * Asserts that the response code is 2xx
     */
    public function assertResponseOk()
    {
        $this->assertStatus(200, 204, 'Expected status code between 200 and 204');
    }

    /**
    * Asserts that the response code is 4xx
    */
    public function assertResponseError()
    {
        $this->assertStatus(400, 429, 'Expected status code between 400 and 429');
    }

    /**
      * Asserts that the response code is 2xx/3xx
      */
    public function assertResponseSuccess()
    {
        $this->assertStatus(200, 308, 'Expected status code between 200 and 308');
    }
    /**
    * Asserts that the response code is 5xx
    */
    public function assertResponseFailure()
    {
        $this->assertStatus(500, 505, 'Expected status code between 500 and 505');
    }

    /**
     * Asserts a specific response code e.g. 200
     */
    public function assertResponseCode(int $code)
    {
        if ($this->response === null) {
            $this->fail('No response');
        }
        $status = $this->response->statusCode();
        $this->assertEquals($code, $status, sprintf('Response code was %s', $code));
    }
    /**
     * Asserts that response contains some text
     */
    public function assertResponseContains(string $text)
    {
        $body = $this->response()->body();
        $this->assertContains($text, $body);
    }

    /**
     * Asserts that response does not contain some text
     */
    public function assertResponseNotContains(string $text)
    {
        $body = $this->response()->body();
        $this->assertNotContains($text, $body);
    }

    /**
     * Asserts that response contains some text
     */
    public function assertResponseEquals(string $expected)
    {
        $body = $this->response()->body();
        $this->assertEquals($expected, $body);
    }

    /**
     * Asserts that response contains some text
     */
    public function assertResponseNotEquals(string $expected)
    {
        $body = $this->response()->body();
        $this->assertNotEquals($expected, $body);
    }
    /**
     * Asserts that redirect has been called to a specific url
     *
     * @param string|array $url
     */
    public function assertRedirect($url)
    {
        $headers = $this->response()->headers();
        if (empty($headers['Location'])) {
            $this->fail('No location set');
        }
        $this->assertEquals(Router::url($url), $headers['Location']);
    }

    /**
     * Asserts that the location header is empty
     */
    public function assertNoRedirect()
    {
        $headers = $this->response()->headers();
        $this->assertTrue(empty($headers['Location']));
    }

    /**
     * Asserts that location header contains specific text
     *
     * @param string $text
     */
    public function assertRedirectContains(string $text)
    {
        $headers = $this->response()->headers();
        if (empty($headers['Location'])) {
            $this->fail('No location set');
        }
        $this->assertContains($text, $headers['Location']);
    }

    /**
     * Asserts that location header contains specific text
     *
     * @param string $text
     */
    public function assertRedirectNotContains(string $text)
    {
        $headers = $this->response()->headers();
        if (empty($headers['Location'])) {
            $this->fail('No location set');
        }
        $this->assertNotContains($text, $headers['Location']);
    }

    public function assertResponseEmpty()
    {
        $body = $this->response()->body();
        $this->assertEmpty($body);
    }


    public function assertResponseNotEmpty()
    {
        $body = $this->response()->body();
        $this->assertNotEmpty($body);
    }

    public function assertHeaderContains(string $header, string $value)
    {
        $headers = $this->response()->headers();
        $this->assertArrayHasKey($header, $headers);
        $this->assertContains($value, $headers[$header]);
    }

    public function assertHeaderNotContains(string $header, string $value)
    {
        $headers = $this->response()->headers();
        $this->assertArrayHasKey($header, $headers);
        $this->assertNotContains($value, $headers[$header]);
    }

    /**
     * Fetches a view variable
     *
     * @param string $key
     * @return string|null
     */
    public function viewVariable(string $key)
    {
        if ($this->controller === null) {
            $this->fail('No request');
        }
        if (isset($this->controller->viewVars[$key])) {
            return $this->controller->viewVars[$key];
        }
        return null;
    }

    /**
     * Sets the headers for the next request
     *
     * @param string $header
     * @param string $value
     */
    public function header(string $header, string $value = null)
    {
        $this->headers[$header] = $value;
    }

    /**
     * Sets the session data for the next request
     *
     * @param array $data
     */
    public function session(array $data)
    {
        $this->session += $data;
    }

    /**
     * Sets a cookie for the next request
     *
     * @todo request
     *
     * @param string $key
     * @param string $value
     */
    public function cookie(string $key, string $value = null)
    {
        $this->cookies[$key] = $value;
    }

    /**
     * Sets server enviroment vars $_SERVER
     *
     * @param string $key
     * @param string $value
     */
    public function env(string $key, string $value)
    {
        $this->env[$key] = $value;
    }

    protected function assertStatus(int $min, int $max, string $errorMessage = 'Invalid status')
    {
        $status = $this->response()->statusCode();
        $this->assertGreaterThanOrEqual($min, $status, $errorMessage);
        $this->assertLessThanOrEqual($max, $status, $errorMessage);
    }

    /**
     * Gets the controller used in the request
     *
     * @return Controller
     */
    public function controller()
    {
        if ($this->controller === null) {
            $this->fail('No controller');
        }
        return $this->controller;
    }

    /**
     * Gets the Request object
     *
     * @return Request
     */
    public function request()
    {
        if ($this->request === null) {
            $this->fail('No request');
        }
        return $this->response;
    }
    /**
     * Gets the response object
     *
     * @return Response
     */
    public function response()
    {
        if ($this->response === null) {
            $this->fail('No response');
        }
        return $this->response;
    }

    protected function sendRequest($url, $method, $data = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $_POST = $_GET = [];
        if ($data) {
            $_POST = $data;
        }

        // Set server env
        foreach ($this->env as $key => $value) {
            $_SERVER[$key] = $value;
        }
            
        $this->request = new Request($url);
        $this->response = $this->getMock(Response::class, ['send','stop']);

        // Send Headers
        foreach ($this->headers as $header => $value) {
            $this->response->header($header, $value);
        }
        // Write session data
        foreach ($this->session as $key => $value) {
            Session::write($key, $value);
        }
    
        $dispatcher = new Dispatcher();
        $this->controller = $dispatcher->dispatch($this->request, $this->response);
    }

    /**
     * IMPORTANT:: call parent::tearDown
     */
    public function tearDown()
    {
        parent::teardown();
        $this->session = $this->headers = $this->cookies = $this->env = [];
        $this->controller = $this->request = $this->response = null;
    }
}
