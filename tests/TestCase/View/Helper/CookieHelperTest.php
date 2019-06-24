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
use Origin\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Cookie;

class CookieHelperTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $_COOKIE = null;
        $_COOKIE = [];
        $this->Cookie = new CookieHelper(new View(new Controller(new Request(), new Response())));
    }
    public function testWrite()
    {
        $this->Cookie->write('foo', 'bar');
        $this->assertArrayHasKey('foo', $this->Cookie->response()->cookies());
    }
    public function testExists()
    {
        $this->assertFalse($this->Cookie->exists('password'));
        $this->Cookie->request()->cookies('foo', 'bar'); // Set in request
        $this->AssertTrue($this->Cookie->exists('foo'));
    }
    public function testRead()
    {
        // This is failing to decrypt when running all tests together
        $_COOKIE['ez'] = 'T3JpZ2lu==.AC3jMXsv4xDAHvdbbj9qTzIedmDcdIcaN1w/FPpji2XGP4iU1dSMTUPWB1HWf5anBj8y7zvZEmA8kyEMbJCGBw==';
        $_COOKIE['normal'] = 'hello';
        $this->Cookie = new CookieHelper(new View(new Controller(new Request(), new Response())));
        $this->assertEquals('hello', $this->Cookie->read('normal'));
        $this->assertEquals('bar', $this->Cookie->read('ez'));
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
        $this->assertFalse($this->Cookie->exists('secret'));
        $this->Cookie->request()->cookies('secret', 'bar'); // Set in request
        $this->assertTrue($this->Cookie->exists('secret'));
        $this->Cookie->destroy();
        $this->assertEquals([], $_COOKIE);
    }
}
