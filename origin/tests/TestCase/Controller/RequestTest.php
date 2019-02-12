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

namespace Origin\Test\Controller;

use Origin\Controller\Request;
use Origin\Exception\MethodNotAllowedException;

class MockRequest extends Request
{
    public $input = null;
    public function setInput($input)
    {
        $this->input = $input;
    }
    protected function readInput()
    {
        return $this->input;
    }
}
class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testParseGet()
    {
        $request = new Request('blog/home?ref=google&source=ppc');

        $this->assertEquals('google', $request->query['ref']);
        $this->assertContains('ppc', $request->query['source']);
    }

    public function testUri()
    {
        $request = new Request();
        $this->assertEquals('/', $request->url);
      
        $_SERVER['REQUEST_URI'] = 'controller/action/100';
        $request = new Request();
        $this->assertEquals('/controller/action/100', $request->url);
    }

    public function testHere()
    {
        $request = new Request('blog/home?ref=google&source=ppc');
        $expected = '/blog/home?ref=google&source=ppc';
        $this->assertEquals($expected, $request->here());
        $request = new Request('blog/home/1234');
        $expected = '/blog/home/1234';
        $this->assertEquals($expected, $request->here());
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
        $this->assertFalse($request->env('FOO'));
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
        $this->assertEquals($expected, $request->data);

        $request = new MockRequest();
       
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request->setInput('{"title":"CNBC","url":""https://www.cnbc.com"}'); // Badd data
        $this->assertEquals([], $request->data);
    }
}
