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

namespace Origin\Test\Middleware;

use Origin\Middleware\CsrfProtectionMiddleware;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Middleware\Exception\InvalidCsrfTokenException;

class MockCsrfProtectionMiddleware extends CsrfProtectionMiddleware
{
    public function token(string $token = null)
    {
        if ($token === null) {
            return $this->token;
        }
        $this->token = $token;
    }

    public function isTestEnvironment()
    {
        return false;
    }
}

class CsrfProtectionMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        
        $middleware->handle($request, $response);

        $this->assertEquals(128, strlen($middleware->token()));
        $this->assertEquals($middleware->token(), $request->params('csrfToken'));
    }

    public function testProcess()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();

        $middleware->process($request, $response);
        $this->assertEquals(128, strlen($middleware->token())); // failsafe
        $this->assertEquals($middleware->token(), $response->cookies('CSRF-Token')['value']);
    }

    public function testMissingCSRFTokenCookie()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        $request->data('title', 'Article Title');
        $this->expectException(InvalidCsrfTokenException::class);
        $middleware->handle($request);
        $middleware->process($request, $response);
    }

    public function testMissingCSRFTokenMismatch()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        $request->data('title', 'Article Title');
        $request->cookies('CSRF-Token', '1234-1234-1234-1234');
        $this->expectException(InvalidCsrfTokenException::class);
        $middleware->handle($request);
        $middleware->process($request, $response);
    }

    public function testValidateTokenForm()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
      
        $request->cookies('CSRF-Token', $middleware->token());

        $request->data('title', 'Article Title');
        $request->data('csrfToken', $middleware->token());
       
        $middleware->handle($request);
        $middleware->process($request, $response);
        $this->assertNull($request->data('csrfToken'));
    }
    public function testValidateTokenHeaders()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();

        $request->cookies('CSRF-Token', $middleware->token());
      
        $request->data('title', 'Article Title');
        $request->headers('X-CSRF-Token', $middleware->token());

        $middleware->handle($request);
        $middleware->process($request, $response);
        $this->assertNull(null);
    }

    /**
     * Test that validate is disabled for test environment
     *
     * @return void
     */
    public function testValidateTokenTestEnvironment()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new CsrfProtectionMiddleware();
        $request->data('title', 'Article Title');
        $middleware->handle($request);
        $middleware->process($request, $response);
        $this->assertNull(null); // This would normally trigger error
    }
}
