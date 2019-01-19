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

class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testParseGet()
    {
        $request = new Request('blog/home?ref=google&source=ppc');

        $this->assertEquals('google', $request->query['ref']);
        $this->assertContains('ppc', $request->query['source']);
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
}
