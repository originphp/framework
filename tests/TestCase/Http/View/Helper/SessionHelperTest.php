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

namespace Origin\Test\Http\View\Helper;

use Origin\Http\View\View;
use Origin\Http\Request;
use Origin\Http\Session;
use Origin\Http\Response;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\SessionHelper;

class MockSessionHelper extends SessionHelper
{
    public function getSession()
    {
        return $this->session();
    }
}

class SessionHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->Session = new MockSessionHelper(new View($controller));
    }

    public function testEngine()
    {
        $this->assertInstanceOf(Session::class, $this->Session->getSession());
    }
    public function testWrite()
    {
        $this->assertNull($this->Session->write('foo', 'bar'));
    }
    public function testRead()
    {
        $this->Session->write('foo', 'bar');
        $this->assertEquals('bar', $this->Session->read('foo'));
    }
    public function testExists()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->exists('foo'));
    }
    
    public function testClear()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->exists('foo'));
        $this->Session->clear();
        $this->assertFalse($this->Session->exists('foo'));
    }
    public function testDelete()
    {
        $this->Session->write('foo', 'bar');
        $this->assertNull($this->Session->delete('foo'));
    }
    public function testDestroy()
    {
        $this->Session->write('foo', 'bar');
        $this->assertNull($this->Session->destroy());
    }
}
