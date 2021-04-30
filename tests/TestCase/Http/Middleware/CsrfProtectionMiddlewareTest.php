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

namespace Origin\Test\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware\CsrfProtectionMiddleware;
use Origin\Http\Middleware\Exception\InvalidCsrfTokenException;

class MockCsrfProtectionMiddleware extends CsrfProtectionMiddleware
{
    protected function isTestEnvironment(): bool
    {
        return false;
    }
}

class CsrfProtectionMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    const SAMPLETOKEN = '18d812304631794e81342e7c1e7bf97e';
    
    public function testCreateToken()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();

        $this->assertTrue($request->is('get'));

        $middleware($request, $response);
       
        $this->assertNotEmpty($response->cookies('CSRF-Token'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}+$/', $response->cookies('CSRF-Token')['value']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}+$/', $request->params('csrfToken'));
    }

    /**
     * @depends testCreateToken
     */
    public function testDisableCSRFProtection()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();

        $request->params('csrfProtection', false);

        $middleware($request, $response);

        $this->assertEmpty($response->cookies('CSRF-Token'));
    }

    public function testHandleGet()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        
        $request->server('REQUEST_METHOD', 'GET');

        $middleware->handle($request, $response);
        $this->assertEquals(32, strlen($request->params('csrfToken')));
    }

    public function testHandleGetWithCookie()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        
        $request->server('REQUEST_METHOD', 'GET');
        $request->cookie('CSRF-Token', self::SAMPLETOKEN);
        $middleware->handle($request, $response);
        $this->assertEquals(self::SAMPLETOKEN, $request->params('csrfToken'));
    }

    /**
     * @depends testHandleGet
     */
    public function testHandleDisabled()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        
        $request->server('REQUEST_METHOD', 'GET');
        $request->params('csrfProtection', false);
        $middleware->handle($request, $response);

        $this->assertEquals(0, strlen($request->params('csrfToken')));
    }

    public function testProcess()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();

        $request->server('REQUEST_METHOD', 'GET');
        $middleware($request, $response);
     
        $this->assertEquals(32, strlen($request->params('csrfToken'))); // check agin
        $this->assertEquals($request->params('csrfToken'), $response->cookies('CSRF-Token')['value']);
    }

    public function testMissingCSRFTokenCookie()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        $request->data('title', 'Article Title');
  
        $this->expectException(InvalidCsrfTokenException::class);
        $middleware($request, $response);
    }

    public function testMissingCSRFTokenMismatch()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
        $request->data('title', 'Article Title');
        $request->cookie('CSRF-Token', '1234-1234-1234-1234');
        $this->expectException(InvalidCsrfTokenException::class);
        $middleware($request, $response);
    }

    public function testValidateTokenForm()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();
      
        $request->cookie('CSRF-Token', self::SAMPLETOKEN);

        $request->data('title', 'Article Title');
        $request->data('csrfToken', self::SAMPLETOKEN);
       
        $middleware($request, $response);
        $this->assertNull($request->data('csrfToken'));
    }
    public function testValidateTokenHeaders()
    {
        $request = new Request();
        $response = new Response();
        $middleware = new MockCsrfProtectionMiddleware();

        $request->cookie('CSRF-Token', self::SAMPLETOKEN);
      
        $request->data('title', 'Article Title');
        $request->header('X-CSRF-Token', self::SAMPLETOKEN);

        $middleware($request, $response);
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
