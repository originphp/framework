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

namespace Origin\Test\Http;

use Origin\Http\Request;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;
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
class RequestTest extends OriginTestCase
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
        $this->deprecated(function () {
            $request = new Request('articles/index', ['server' => ['FOO' => 'BAR']]);
     
            $this->assertNull($request->env('BAR'));
            $this->assertEquals('BAR', $request->env('FOO'));
    
            $request = new Request();
            $request->env('FOO', 'BAR');
            $this->assertEquals('BAR', $request->env('FOO'));
        });
    }
    public function testJsonPost()
    {
        $request = new Request('/articles/index', [
            'server' => [
                'CONTENT_TYPE' => 'application/json',
                'REQUEST_METHOD' => 'POST'
            ],
            'input' => '{"title":"CNBC","url":"https://www.cnbc.com"}'
        ]);
       
        $expected = ['title' => 'CNBC','url' => 'https://www.cnbc.com'];
        $this->assertEquals($expected, $request->data());

        $request = new Request('/articles/index', [
            'server' => [
                'CONTENT_TYPE' => 'application/json',
                'REQUEST_METHOD' => 'POST'
            ],
            'input' => '{"title":"CNBC","url":""https://www.cnbc.com"}' // BAD JSON
        ]);
     
        $this->assertEquals([], $request->data());
    }

    public function testQuery()
    {
 
        // changed to use new SETTER
        $request = new Request();
        $request->query('key', 'value');
        $this->assertEquals('value', $request->query('key'));
        $this->assertEquals(['key' => 'value'], $request->query());
        $this->assertNull($request->query('fozzy'));
    }

    public function testData()
    {
        $request = new Request();
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

    public function testContentType()
    {
        $request = new MockRequest('/', ['server' => ['CONTENT_TYPE' => 'application/json']]);
        $this->assertEquals('application/json', $request->contentType());
    }

    public function testFiles()
    {
        $request = new Request('/', ['files' => ['file' => 'dummy file']]);
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
    public function testHeadersDeprecated()
    {
        $this->deprecated(function () {
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
        });
    }

    public function testHeaders()
    {
        $request = new MockRequest();
        $request->header('WWW-Authenticate', 'Negotiate');
        $request->header('Content-Type', 'application/pdf');

        $this->assertEquals('Negotiate', $request->headers('WWW-Authenticate'));
        $this->assertEquals('application/pdf', $request->headers('content-type')); // Case
        $this->assertEquals(['WWW-Authenticate' => 'Negotiate','Content-Type' => 'application/pdf'], $request->headers());

        $this->assertEquals(null, $request->headers('secret'));
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
    public function testisSsl()
    {
        $request = new MockRequest();
        $this->assertFalse($request->isSsl());
        $request = new MockRequest(null, ['server' => ['HTTPS' => 'off']]);
        $this->assertFalse($request->isSsl());
        $request = new MockRequest(null, ['server' => ['HTTPS' => 'on']]);
        $this->assertTrue($request->isSsl());
        $request = new MockRequest(null, ['server' => ['HTTPS' => 1]]);
        $this->assertTrue($request->isSsl());
    }
    public function testisAjax()
    {
        $request = new MockRequest();
        $this->assertFalse($request->isAjax());
    
        $request = new MockRequest(null, ['server' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']]);
        $this->assertTrue($request->isAjax());
    }

    public function testisJson()
    {
        $request = new MockRequest();
        $this->assertFalse($request->isJson());
    
        $request = new MockRequest('/posts/1000.json');
        $this->assertTrue($request->isJson());

        $request = new MockRequest(null, ['server' => ['HTTP_ACCEPT' => 'application/json']]);
        $this->assertTrue($request->isJson());
    }

    public function testisXml()
    {
        $request = new MockRequest();
        $this->assertFalse($request->isXml());

        $request = new MockRequest('/posts/1000.xml');
        $this->assertTrue($request->isXml());
    
        $request = new MockRequest(null, ['server' => ['HTTP_ACCEPT' => 'application/xml']]);
        $this->assertTrue($request->isXml());

        $request = new MockRequest(null, ['server' => ['HTTP_ACCEPT' => 'text/xml']]);
        $this->assertTrue($request->isXml());
    }
   
    public function testReferer()
    {
        $request = new MockRequest(null, ['server' => ['HTTP_REFERER' => 'https://www.google.com/search?q=top+php+frameworks']]);
        $this->assertEquals('https://www.google.com/search?q=top+php+frameworks', $request->referer());
    }

    public function testType()
    {
        // Test Accepts
        $request = new MockRequest(null, ['server' => ['HTTP_ACCEPT' => 'application/json']]);
        $this->assertEquals('json', $request->respondAs());

        $request = new MockRequest(null, ['server' => ['HTTP_ACCEPT' => 'application/xml']]);
        $this->assertEquals('xml', $request->respondAs());
    }

    public function testFill()
    {
        $request = new Request('/bookmarks/index?foo=bar', ['server' => []]);

        $this->assertEquals('/bookmarks/index?foo=bar', $request->server('REQUEST_URI'));
        $this->assertEquals('foo=bar', $request->server('QUERY_STRING'));
        $this->assertEquals('GET', $request->server('REQUEST_METHOD'));
    }
}
