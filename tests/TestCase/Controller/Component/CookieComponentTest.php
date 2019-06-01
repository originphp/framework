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

namespace Origin\Test\Controller\Component;

use Origin\Controller\Controller;
use Origin\Controller\Component\CookieComponent;
use Origin\Http\Request;
use Origin\Http\Response;

class CookieComponentTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $_COOKIE = [];
        $this->Cookie = new CookieComponent(new Controller(new Request(), new Response()));
    }
    public function testWrite()
    {
        $this->Cookie->write('foo', 'bar');
        $this->assertArrayHasKey('foo', $this->Cookie->response()->cookies());
    }
    public function testCheck()
    {
        $this->assertFalse($this->Cookie->check('password'));
        $_COOKIE['foo'] = '1234';
        $this->AssertTrue($this->Cookie->check('foo'));
    }
    public function testRead()
    {
        $_COOKIE['foo'] = 'T3JpZ2lu==.J4pNTvNegB4yYV1wgUK6eazwy+DSQhgAeOqmIIo7oeQ=';
        $this->assertEquals('bar', $this->Cookie->read('foo'));
    }
    public function testDelete()
    {
        $this->Cookie->delete('foo');
        $cookies = $this->Cookie->response()->cookies();
        $this->assertEquals('', $cookies['foo']['value']);
        $this->assertTrue($cookies['foo']['expire'] < time());
    }

    public function testdestroy()
    {
        $this->assertFalse($this->Cookie->check('secret'));
        $_COOKIE['secret'] = '12456789';
        $this->assertTrue($this->Cookie->check('secret'));
        $this->Cookie->destroy();
        $this->assertEquals([], $_COOKIE);
    }
}
