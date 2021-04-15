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

namespace Origin\Test\Http\Controller\Component;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Controller\Controller;
use Origin\Http\Controller\Component\CookieComponent;

class CookieComponentTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $_COOKIE = [];
        $this->Cookie = new CookieComponent(new Controller(new Request(), new Response()));
    }
    public function testWrite()
    {
        $this->Cookie->write('foo', 'bar');
        $this->assertArrayHasKey('foo', $this->Cookie->response()->cookies());
    }
    public function testExists()
    {
        $this->assertFalse($this->Cookie->exists('password'));
        $this->Cookie->request()->cookies->set('foo', 'bar'); // Set in request
        $this->AssertTrue($this->Cookie->exists('foo'));
    }
    public function testRead()
    {
        $_COOKIE['encrypted'] = 'T3JpZ2lu==.kP0v9J9WkslW5qU9YszzOVLBOpXtxhQUFkSklzbcZ/kJZpXORabtaSHSeSabFkuiSv90FoNo4dVSSZbRARz9rw==';
        $this->Cookie = new CookieComponent(new Controller(new Request(), new Response()));
        $this->assertEquals('bar', $this->Cookie->read('encrypted'));
    }
    public function testDelete()
    {
        $this->Cookie->delete('foo');
        $cookies = $this->Cookie->response()->cookies();
        $this->assertEquals('', $cookies['foo']['value']);
        $this->assertTrue($cookies['foo']['expires'] < time());
    }

    public function testdestroy()
    {
        $this->assertFalse($this->Cookie->exists('secret'));
        $this->Cookie->request()->cookies->set('secret', 'bar'); // Set in request
        $this->assertTrue($this->Cookie->exists('secret'));
        $this->Cookie->destroy();
        $this->assertEquals([], $_COOKIE);
    }
}
