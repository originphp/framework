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

namespace Origin\Test\View\Helper;

use Origin\View\View;
use Origin\View\Helper\CookieHelper;
use Origin\View\Helper\FormHelper;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Core\Cookie;

class MockCookieHelper extends CookieHelper
{
    public function initialize(array $config)
    {
        $this->cookie = new MockEngine();
    }
    public function getCookie()
    {
        $this->cookie = null;
        return $this->cookie();
    }
}
class MockEngine extends \Origin\Core\Cookie
{
    protected function setCookie($name, $value, $expire=0, $path='/', $domain='', $secure=false, $httpOnly=false)
    {
        $_COOKIE[$name] = $value;
    }
}
class CookieHelperTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $controller = new Controller(new Request(), new Response());
        $this->Cookie = new MockCookieHelper(new View($controller));
    }

    public function testEngine()
    {
        $this->assertInstanceOf(Cookie::class, $this->Cookie->getCookie());
    }
    public function testWrite()
    {
        $this->assertNull($this->Cookie->write('foo', 'bar'));
    }
    public function testRead()
    {
        $this->Cookie->write('foo', 'bar');
        $this->assertEquals('bar', $this->Cookie->read('foo'));
    }
    public function testCheck()
    {
        $this->Cookie->write('foo', 'bar');
        $this->assertTrue($this->Cookie->check('foo'));
    }
    public function testDelete()
    {
        $this->Cookie->write('foo', 'bar');
        $this->assertNull($this->Cookie->delete('foo'));
    }
    public function testDestroy()
    {
        $this->Cookie->write('foo', 'bar');
        $this->assertNull($this->Cookie->destroy());
    }
}
