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

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Dispatcher;
use Origin\Http\Router;
use App\Application;

/**
 * A way to test controllers from a higher level
 */

trait IntegrationTestTrait
{
    /**
     * Holds the response object
     *
     * @var \Origin\Http\Response
     */
    protected $response = null;
    /**
     * Holds the response object
     *
     * @var \Origin\Http\Request
     */
    protected $request = null;
    /**
     * Holds the controller for the most recent request
     *
     * @var \Origin\Controller\Controller
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
     * Flag on how to handle with middleware
     *
     * @var boolean
     */
    protected $testWithMiddleware = true;

    /**
     * Enables and disables testing with middleware
     *
     * @param boolean $bool
     * @return void
     */
    public function useMiddleware(bool $bool)
    {
        $this->testWithMiddleware = $bool;
    }

    /**
     * Sends a GET request
     *
     * @param string $url
     * @return void
     */
    public function get(string $url)
    {
        $this->sendRequest('GET', $url);
    }
    /**
     * Sends a post request
     *
     * @param string $url
     * @param array $data data to send as post
     * @return void
     */
    public function post(string $url, array $data = [])
    {
        $this->sendRequest('POST', $url, $data);
    }
    
    /**
     * Sends a DELETE request
     *
     * @param string $url
     * @return void
     */
    public function delete(string $url)
    {
        $this->sendRequest('DELETE', $url);
    }
    /**
     * Sends a PATCH request
     *
     * @param string $url
     * @param array $data array of data to send as patch request
     * @return void
     */
    public function patch(string $url, array $data = [])
    {
        $this->sendRequest('PATCH', $url, $data);
    }
  
    /**
     * Sends a PUT request
     *
     * @param string $url
     * @param array $data array of data to send as put request
     * @return void
     */
    public function put(string $url, array $data = [])
    {
        $this->sendRequest('PUT', $url, $data);
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

    /**
     * Gets the controller used in the request
     *
     * @return \Origin\Controller\Controller
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
     * @return \Origin\Http\Request
     */
    public function request()
    {
        if ($this->request === null) {
            $this->fail('No request');
        }
        return $this->request;
    }
    /**
     * Gets the response object
     *
     * @return \Origin\Http\Response
     */
    public function response()
    {
        if ($this->response === null) {
            $this->fail('No response');
        }
        return $this->response;
    }

    /**
     * Sends the request for the test
     *
     * @param string $method
     * @param string|array $url
     * @param array $data
     * @return void
     */
    protected function sendRequest(string $method, $url, array $data = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $_SESSION = $_POST = $_GET = $_COOKIE = [];
        if ($data) {
            $_POST = $data;
        }

        // Set server env
        foreach ($this->env as $key => $value) {
            $_SERVER[$key] = $value;
        }
            
        $this->request = new Request($url);
        $this->request->session()->destroy();
       
        $this->response = $this->getMockBuilder(Response::class)
                            ->setMethods(['send','stop'])
                            ->getMock();

        // Write session data
        foreach ($this->session as $key => $value) {
            $this->request->session()->write($key, $value);
        }

        // Send Headers
        foreach ($this->headers as $header => $value) {
            $this->response->header($header, $value);
        }
        
        // Write cookie data for request
        foreach ($this->cookies as $name => $value) {
            $this->response->cookie($name, $value);
        }

        if ($this->testWithMiddleware) {
            $application = new Application($this->request, $this->response);
            $this->controller = Dispatcher::instance()->controller();
        } else {
            $dispatcher = new Dispatcher();
            $dispatcher->dispatch($this->request, $this->response);
            $this->controller = $dispatcher->controller();
        }
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

    /**
    * Assert that the response has a 400 status code.
    */
    public function assertResponseBadRequest()
    {
        $this->assertResponseCode(400);
    }
    /**
     * Assert that the response has a 401 status code.
     */
    public function assertResponseUnauthorized()
    {
        $this->assertResponseCode(401);
    }

    /**
     * Asserts that the response has a 404 not found status code
     *
     * @return void
     */
    public function assertResponseNotFound()
    {
        $this->assertResponseCode(404);
    }
    /**
    * Assert that the response has a 403 status code.
    */
    public function assertResponseForbidden()
    {
        $this->assertResponseCode(403);
    }

    /**
     * Asserts that the response code is 2xx
     */
    public function assertResponseOk()
    {
        $this->assertStatusBetween(200, 204, 'Expected status code between 200 and 204');
    }

    /**
    * Asserts that the response code is 4xx
    */
    public function assertResponseError()
    {
        $this->assertStatusBetween(400, 429, 'Expected status code between 400 and 429');
    }

    /**
      * Asserts that the response code is 2xx/3xx
      */
    public function assertResponseSuccess()
    {
        $this->assertStatusBetween(200, 308, 'Expected status code between 200 and 308');
    }
    /**
    * Asserts that the response code is 5xx
    */
    public function assertResponseFailure()
    {
        $this->assertStatusBetween(500, 505, 'Expected status code between 500 and 505');
    }

    protected function assertStatusBetween(int $min, int $max, string $errorMessage = 'Invalid status')
    {
        $status = $this->response()->statusCode();
        $this->assertGreaterThanOrEqual($min, $status, $errorMessage);
        $this->assertLessThanOrEqual($max, $status, $errorMessage);
    }

    /**
     * Asserts a specific response code e.g. 200
     *
     *  200 - OK (Success)
     *  400 - Bad Request (Failure - client side problem)
     *  500 - Internal Error (Failure - server side problem)
     *  401 - Unauthorized
     *  404 - Not Found
     *  403 - Forbidden (For application level permisions)
     *
     * @see https://www.restapitutorial.com/httpstatuscodes.html
     * @param integer $statusCode
     * @return void
     */
    public function assertResponseCode(int $code)
    {
        $status = $this->response()->statusCode();
        $this->assertEquals($code, $status, sprintf('Response code was %s', $code));
    }
    /**
     * Asserts that response contains some text
     */
    public function assertResponseContains(string $text)
    {
        $body = (string) $this->response()->body();
        $this->assertContains($text, $body);
    }

    /**
     * Asserts that response does not contain some text
     */
    public function assertResponseNotContains(string $text)
    {
        $body =  (string) $this->response()->body();
        $this->assertNotContains($text, $body);
    }

    /**
     * Asserts that response equals
     */
    public function assertResponseEquals(string $expected)
    {
        $body = (string) $this->response()->body();
        $this->assertEquals($expected, $body);
    }

    /**
     * Asserts that response contains some text
     */
    public function assertResponseNotEquals(string $expected)
    {
        $body = (string) $this->response()->body();
        $this->assertNotEquals($expected, $body);
    }
    /**
     * Asserts that the location header is correct.
     *
     * @param string|array $url The url where the client is expected to goto. Leave null just to check location header exists
     */
    public function assertRedirect($url = null)
    {
        $headers = $this->response()->headers();
        $this->assertArrayHasKey('Location', $headers, 'Location header not set');
        if ($url) {
            $this->assertEquals(Router::url($url), $headers['Location']);
        }
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

    /**
     * Asserts that the response is empty
     *
     * @return void
     */
    public function assertResponseEmpty()
    {
        $body = $this->response()->body();
        $this->assertEmpty($body);
    }

    /**
     * Asserts that response is not empty
     *
     * @return void
     */
    public function assertResponseNotEmpty()
    {
        $body = $this->response()->body();
        $this->assertNotEmpty($body);
    }

    /**
     * Asserts a response header
     *
     * @param string $header
     * @param string $value
     * @return void
     */
    public function assertHeader(string $header, string $value)
    {
        $headers = $this->response()->headers();
        $this->assertArrayHasKey($header, $headers);
        $this->assertEquals($headers[$header], $value);
    }

    /**
     * Asserts a response header contains a string
     *
     * @param string $header
     * @param string $value
     * @return void
     */
    public function assertHeaderContains(string $header, string $value)
    {
        $headers = $this->response()->headers();
        $this->assertArrayHasKey($header, $headers);
        $this->assertContains($value, $headers[$header]);
    }

    /**
     * Asserts a response header does not contain a string
     *
     * @param string $header
     * @param string $value
     * @return void
     */
    public function assertHeaderNotContains(string $header, string $value)
    {
        $headers = $this->response()->headers();
        $this->assertArrayHasKey($header, $headers);
        $this->assertNotContains($value, $headers[$header]);
    }

    /**
     * Assert a cookie value
     *
     * @param string $cookie
     * @param string $value
     * @return void
     */
    public function assertCookie(string $cookie, string $value)
    {
        $cookies = $this->response()->cookies();
        $this->assertArrayHasKey($cookie, $cookies);
        $this->assertEquals($cookies[$cookie]['value'], $value);
    }
}
