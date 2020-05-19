<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Http;

use Origin\Http\Request;
use Origin\TestSuite\TestTrait;
use Origin\Http\Exception\MethodNotAllowedException;

class MockRequest extends Request
{
    use TestTrait;
    protected $input = null;
    public function setInput($input)
    {
        $this->input = $input;
    }
    protected function readInput(): ?string
    {
        return $this->input;
    }
}
class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testParseGet()
    {
        $request = new Request('blog/home?ref=google&source=ppc');

        $this->assertEquals('google', $request->query('ref'));
        $this->assertStringContainsString('ppc', $request->query('source'));
    }

    public function testUrl()
    {
        $request = new Request('blog/home?ref=google&source=ppc');
        $expected = 'http://localhost/blog/home?ref=google&source=ppc';
        $this->assertEquals($expected, $request->url(true));
        $request = new Request('blog/home/1234');
        $expected = 'http://localhost/blog/home/1234';
        $this->assertEquals($expected, $request->url());
    }

    public function testPath()
    {
        $request = new Request('blog/home?ref=google&source=ppc');
        $expected = '/blog/home?ref=google&source=ppc';
        $this->assertEquals($expected, $request->path(true));
        $request = new Request('blog/home/1234');
        $expected = '/blog/home/1234';
        $this->assertEquals($expected, $request->path());
    }
    
    public function testIs()
    {
        $request = new Request('articles/index', ['server' => ['REQUEST_METHOD' => 'POST']]);
    
        $this->assertTrue($request->is(['post']));
        $this->assertFalse($request->is('get'));
        
        $request = new Request('articles/index', ['server' => ['REQUEST_METHOD' => 'GET']]);
        $this->assertFalse($request->is('post'));
        $this->assertTrue($request->is('get'));
    }
    public function testAllowMethod()
    {
        $request = new Request('articles/index', ['server' => ['REQUEST_METHOD' => 'POST']]);
        $this->assertTrue($request->allowMethod(['post']));
      
        $request = new Request('articles/index', ['server' => ['REQUEST_METHOD' => 'GET']]);
        $this->expectException(MethodNotAllowedException::class);
        $request->allowMethod(['delete']);
    }
    public function testEnv()
    {
        $request = new Request('articles/index', ['server' => ['FOO' => 'BAR']]);
        $this->assertNull($request->env('BAR'));
        $this->assertEquals('BAR', $request->env('FOO'));

        $request = new Request();
        $request->env('FOO', 'BAR');
        $this->assertEquals('BAR', $request->env('FOO'));
    }
    public function testJsonPost()
    {
        $request = new MockRequest();
       
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request->setInput('{"title":"CNBC","url":"https://www.cnbc.com"}');

        $request->initialize('articles/index');
        $expected = ['title' => 'CNBC','url' => 'https://www.cnbc.com'];
        $this->assertEquals($expected, $request->data());

        $request = new MockRequest();
       
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request->setInput('{"title":"CNBC","url":""https://www.cnbc.com"}'); // Badd data
        $this->assertEquals([], $request->data());
    }

    public function testQuery()
    {
        $request = new MockRequest();
        $request->query('key', 'value');
        $this->assertEquals('value', $request->query('key'));
        $this->assertEquals(['key' => 'value'], $request->query());
        $this->assertNull($request->query('fozzy'));
    }

    public function testData()
    {
        $request = new MockRequest();
        $request->data('key', 'value');
        $this->assertEquals('value', $request->data('key'));
        $this->assertEquals(['key' => 'value'], $request->data());
        $this->assertNull($request->data('fozzy'));
        $data = ['foo' => 'bar'];
        $request->data($data); // test replace
        $this->assertEquals($data, $request->data());
    }

    public function testParams()
    {
        $request = new MockRequest();
        $request->params('key', 'value');
        $this->assertEquals('value', $request->params('key'));
        $this->assertNotEmpty($request->params());
        $this->assertNull($request->params('fozzy'));
        $data = ['foo' => 'bar'];
        $request->params($data); // test replace
        $this->assertEquals($data, $request->params());
    }

    public function testMethod()
    {
        $request = new MockRequest('/', ['server' => ['REQUEST_METHOD' => 'POST']]);
        $this->assertEquals('POST', $request->method());
    }

    public function testFiles()
    {
        $request = new MockRequest('/', ['files' => ['file' => 'dummy file']]);
        $this->assertEquals('dummy file', $request->data('file'));
    }

    public function testCookies()
    {
        $_COOKIE = [
            'foo' => 'T3JpZ2lu==.sohTIjiPjvT+n6OUcASsZZ4Umymfravo53rhwG2iNbf4Jp/jl9ZDO0zQubXR/DRBstaW+nEnDXUhJ9PNDsdiDQ==',
        ];
        $expected = [
            'foo' => 'This is a test',
        ];
        $request = new MockRequest();
    
        $this->assertEquals($expected, $request->cookies());
        $this->assertEquals('This is a test', $request->cookies('foo'));

        $expected = [
            'foo' => 'bar',
        ];
        $request->cookies($expected);
        $this->assertEquals($expected, $request->cookies());// test replace
    }
    public function testHeaders()
    {
        $request = new MockRequest();
        $request->header('WWW-Authenticate', 'Negotiate');
        $request->header('Content-type: application/pdf');

        $this->assertEquals('Negotiate', $request->headers('WWW-Authenticate'));
        $this->assertEquals('Negotiate', $request->headers('www-authenticate')); // PSR friendly
        $this->assertEquals(['WWW-Authenticate' => 'Negotiate','Content-type' => 'application/pdf'], $request->headers());

        $this->assertEquals(null, $request->headers('secret'));

        $expected = [
            'X-Extra' => 'Foo',
            'Location' => 'https://www.originphp.com'
        ];
        $request->headers($expected);
        $this->assertEquals($expected, $request->headers());
    }

    /**
     * @depends testHeaders
     */
    public function testAcceptLanguage()
    {
        $request = new MockRequest();
        $request->header('Accept-Language', 'en-GB,en;q=0.9,es;q=0.8');
        $this->assertTrue($request->acceptLanguage('en'));
        $this->assertEquals(['en_GB','en','es'], $request->acceptLanguage());
    }
    /**
       * @depends testHeaders
       */
    public function testAccepts()
    {
        $request = new MockRequest();
        $this->assertFalse($request->accepts('application/json'));
        $request = new MockRequest('/controller/action.json');
        $this->assertTrue($request->accepts('application/json'));
        $request = new MockRequest('/api/search.json');
        $this->assertTrue($request->accepts('application/json'));

        $request = new MockRequest();
        $request->header('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3');
        $accepts = $request->accepts();
        $this->assertEquals('text/html', $accepts[0]);
        $this->assertTrue($request->accepts(['application/xml','application/json']));
    }

    public function testHost()
    {
        $request = new MockRequest(null, ['server' => ['HTTP_HOST' => '127.0.0.1','HTTP_X_FORWARDED_HOST' => '127.0.0.2']]);
        $this->assertEquals('127.0.0.1', $request->host());
        $request = new MockRequest(null, ['server' => ['HTTP_HOST' => '127.0.0.1','HTTP_X_FORWARDED_HOST' => '127.0.0.2']]);
        $this->assertEquals('127.0.0.2', $request->host(true));
    }

    public function testIp()
    {
        $request = new MockRequest(null, ['server' => ['REMOTE_ADDR' => '127.0.0.1']]);
        $this->assertEquals('127.0.0.1', $request->ip());
        $request = new MockRequest(null, ['server' => ['HTTP_CLIENT_IP' => '127.0.0.1']]);
        $this->assertEquals('127.0.0.1', $request->ip());
        $request = new MockRequest(null, ['server' => ['HTTP_X_FORWARDED_FOR' => '127.0.0.1']]);
        $this->assertEquals('127.0.0.1', $request->ip());
        $request = new MockRequest();
        $this->assertNull($request->ip());
    }
    public function testSsl()
    {
        $request = new MockRequest();
        $this->assertFalse($request->ssl());
        $request = new MockRequest(null, ['server' => ['HTTPS' => 'off']]);
        $this->assertFalse($request->ssl());
        $request = new MockRequest(null, ['server' => ['HTTPS' => 'on']]);
        $this->assertTrue($request->ssl());
        $request = new MockRequest(null, ['server' => ['HTTPS' => 1]]);
        $this->assertTrue($request->ssl());
    }
    public function testAjax()
    {
        $request = new MockRequest();
        $this->assertFalse($request->ajax());
    
        $request = new MockRequest(null, ['server' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']]);
        $this->assertTrue($request->ajax());
    }
   
    public function testReferer()
    {
        $request = new MockRequest(null, ['server' => ['HTTP_REFERER' => 'https://www.google.com/search?q=top+php+frameworks']]);
        $this->assertEquals('https://www.google.com/search?q=top+php+frameworks', $request->referer());
    }

    public function testType()
    {
        $request = new MockRequest();
        $request->params('type', 'json');
        $request->callMethod('detectRequestType');
        $this->assertEquals('json', $request->type());

        // Test Accepts
        $request = new MockRequest(null, ['server' => ['HTTP_ACCEPT' => 'application/json']]);
        $this->assertEquals('json', $request->type());

        $request = new MockRequest(null, ['server' => ['HTTP_ACCEPT' => 'application/xml']]);
        $this->assertEquals('xml', $request->type());
    }
}
