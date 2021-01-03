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

use Origin\Http\Cookie;
use Origin\Http\Controller\Controller;

class MockCookie extends Cookie
{
    protected $cookies = [];

    protected function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = false): void
    {
        $this->cookies[$name] = $value;
    }
    
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function getCookie($name)
    {
        return $this->cookies[$name] ?? null;
    }
}

class OrangesController extends Controller
{
    protected $autoRender = false;

    public function index()
    {
    }
}
class CookieTest extends \PHPUnit\Framework\TestCase
{
    public function testRead()
    {
        $cookie = new MockCookie();

        // test response writing
        $cookie->write('foo', 'bar');
        $this->assertArrayHasKey('foo', $cookie->cookies());
        $this->assertNotNull($cookie->getCookie('foo'));
    }

    public function testWrite()
    {
        $cookie = new MockCookie();

        // test cookie jar reading - decode cookie
        $_COOKIE['testReadWrite'] = 'T3JpZ2lu==.VgTrOgIFsXyAEEtY625XQXoVpc7agJlAb2Q0jDYL8utJDjVet7KjHsGkB3U31qwD1vdwtoaIMDkVzKBsgo3KpQ==';
        $this->assertEquals('This is a test', $cookie->read('testReadWrite'));
   
        $this->assertNull($cookie->read('notSet'));
    }

    public function testExists()
    {
        $cookie = new MockCookie();
        $_COOKIE['foo'] = true;
        $this->assertTrue($cookie->exists('foo'));
        $this->assertFalse($cookie->exists('bar'));
    }
    public function testDelete()
    {
        $cookie = new MockCookie();
        $_COOKIE['foo'] = 'bar';
        $this->assertNull($cookie->delete('foo'));
        $this->assertFalse(isset($_COOKIE['foo']));

        $this->assertNull($cookie->delete('notSet'));
    }

    public function testDestroy()
    {
        $cookie = new MockCookie();
        $_COOKIE['testDestroy'] = 'PLgTmbAZY5BZjA9tQbp5h50GI1wbXZldGV9cERAx5c6C4nvWBH8Ouc+tGbX+1mfv';
        $cookie->destroy();
        $this->assertEmpty($_COOKIE);
    }

    public function testPack()
    {
        $cookie = new MockCookie();
        $cookie->write('testPack', [1 => 'one'], ['encrypt' => false]);
        $this->assertEquals('{"1":"one"}', $cookie->cookies()['testPack']);
        $_COOKIE['testPack'] = '{"1":"one"}';
        $this->assertEquals([1 => 'one'], $cookie->read('testPack'));
    }
}
