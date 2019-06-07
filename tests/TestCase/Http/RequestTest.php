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

namespace Origin\Test\Http;

use Origin\Http\Request;
use Origin\Exception\MethodNotAllowedException;

class MockRequest extends Request
{
    public $input = null;
    public function setInput($input)
    {
        $this->input = $input;
    }
    protected function readInput() : ?string
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
        $this->assertContains('ppc', $request->query('source'));
    }

    public function testUri()
    {
        $request = new Request();
        $this->assertEquals('/', $request->url());
      
        $_SERVER['REQUEST_URI'] = 'controller/action/100';
        $request = new Request();
        $this->assertEquals('/controller/action/100', $request->url());
    }

    public function testHere()
    {
        $request = new Request('blog/home?ref=google&source=ppc');
        $expected = '/blog/home?ref=google&source=ppc';
        $this->assertEquals($expected, $request->url(true));
        $request = new Request('blog/home/1234');
        $expected = '/blog/home/1234';
        $this->assertEquals($expected, $request->url());
    }

    public function testIs()
    {
        $request = new Request('articles/index');
    
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue($request->is(['post']));
        $this->assertFalse($request->is('get'));
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse($request->is('post'));
        $this->assertTrue($request->is('get'));
        unset($_SERVER['REQUEST_METHOD']);
    }
    public function testAllowMethod()
    {
        $request = new Request('articles/index');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue($request->allowMethod(['post']));
      
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->expectException(MethodNotAllowedException::class);
        $request->allowMethod(['delete']);
    }
    public function testEnv()
    {
        $request = new Request('articles/index');
        $this->assertNull($request->env('FOO'));
        $_SERVER['FOO'] = 'bar';
        $this->assertEquals('bar', $request->env('FOO'));
    }
    public function testJsonPost()
    {
        $request = new MockRequest();
       
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request->setInput('{"title":"CNBC","url":"https://www.cnbc.com"}');

        $request->initialize('articles/index');
        $expected=['title'=>'CNBC','url'=>'https://www.cnbc.com'];
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
        $this->assertEquals(['key'=>'value'], $request->query());
        $this->assertNull($request->query('fozzy'));
    }

    public function testData()
    {
        $request = new MockRequest();
        $request->data('key', 'value');
        $this->assertEquals('value', $request->data('key'));
        $this->assertEquals(['key'=>'value'], $request->data());
        $this->assertNull($request->data('fozzy'));
    }

    public function testParams()
    {
        $request = new MockRequest();
        $request->params('key', 'value');
        $this->assertEquals('value', $request->params('key'));
        $this->assertNotEmpty($request->params());
        $this->assertNull($request->params('fozzy'));
    }

    public function testMethod()
    {
        $request = new MockRequest();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('POST', $request->method());
        $_SERVER['REQUEST_METHOD'] = '';
    }

    public function testFiles()
    {
        $_FILES = ['file'=>'dummy file'];
        $request = new MockRequest();
        $this->assertEquals('dummy file', $request->data('file'));
    }

    public function testCookie()
    {
        $_COOKIE = [
            'foo'=>'T3JpZ2lu==.3tAgxpBtEQ7tQFeVaf76XnwekKafaUmby9a7QzQUKjo='
        ];
        $expected = [
            'foo' => 'This is a test'
        ];
        $request = new MockRequest();
        $this->assertEquals($expected, $request->cookies());
        $this->assertEquals('This is a test', $request->cookies('foo'));
    }
    public function testHeaders()
    {
        $request = new MockRequest();
        $request->headers('WWW-Authenticate', 'Negotiate');
        $request->headers('HTTP/1.0 404 Not Found',null);

        $this->assertEquals('Negotiate', $request->headers('WWW-Authenticate'));
        $this->assertEquals('Negotiate', $request->headers('www-authenticate')); // PSR friendly
        $this->assertEquals(['WWW-Authenticate'=>'Negotiate','HTTP/1.0 404 Not Found'=>null], $request->headers());

        $this->assertEquals(null, $request->headers('secret')); 
    }

    /**
     * @depends testHeaders
     */
    public function testAcceptLanguage()
    {
        $request = new MockRequest();
        $request->headers('Accept-Language', 'en-GB,en;q=0.9,es;q=0.8');
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
        $request = new MockRequest();
        $request->params('json', true);
        $this->assertTrue($request->accepts('application/json'));

        $request = new MockRequest();
        $request->headers('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3');
        $accepts = $request->accepts();
        $this->assertEquals('text/html', $accepts[0]);
        $this->assertTrue($request->accepts(['application/xml','application/json']));
    }
}
