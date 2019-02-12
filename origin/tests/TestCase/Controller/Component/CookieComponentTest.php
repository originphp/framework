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

use Origin\Controller\Component\CookieComponent;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;

class MockCookieComponent extends CookieComponent
{
    protected $cookies = [];

    protected function setCookie(string $name, array $value)
    {
        $this->cookies[$name] = $value;
    }
    
    public function cookies()
    {
        return $this->cookies;
    }
}

class OrangesController extends Controller
{
    public $autoRender = false;

    public function index()
    {
    }
}
class CookieComponentTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $request = new Request('/oranges/index');
        $this->Cookie = new MockCookieComponent(new OrangesController($request, new Response()));
    }
    public function testReadWrite()
    {
        $request = new Request('/oranges/index');
        $Cookie = new CookieComponent(new OrangesController($request, new Response()));

        // test response writing
        $Cookie->write('foo', 'bar');
        $this->assertEquals('bar', $Cookie->read('foo'));

        // test cookie jar reading
        $_COOKIE['testReadWrite'] = 'PLgTmbAZY5BZjA9tQbp5h50GI1wbXZldGV9cERAx5c6C4nvWBH8Ouc+tGbX+1mfv';
        $this->assertEquals('This is a test', $Cookie->read('testReadWrite'));

        $this->assertNull($Cookie->read('notSet'));
    }

    public function testCheck()
    {
        $request = new Request('/oranges/index');
        $Cookie = new CookieComponent(new OrangesController($request, new Response()));
        
        $Cookie->write('foo', 'bar');
        $_COOKIE['testCheck'] = true;
        $this->assertTrue($Cookie->check('foo'));
        $this->assertTrue($Cookie->check('testCheck'));
        $this->assertFalse($Cookie->check('bar'));
    }
    public function testDelete()
    {
        $Cookie = $this->Cookie;

        $Cookie->write('foo', 'bar');
        $expected = ['foo'=>['expire'=>time() - 3600]];
        $this->assertNull($Cookie->delete('foo'));
        $this->assertEquals($expected, $Cookie->cookies());

        $this->assertNull($Cookie->delete('notSet'));
    }

    public function testDestroy()
    {
        $Cookie = $this->Cookie;
        $_COOKIE['testDestroy'] = 'PLgTmbAZY5BZjA9tQbp5h50GI1wbXZldGV9cERAx5c6C4nvWBH8Ouc+tGbX+1mfv';
        $Cookie->destroy();
        $this->assertEmpty($_COOKIE);
    }
}
