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

namespace Origin\Test\Http\Controller\Component;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Controller\Controller;
use Origin\Http\Controller\Component\SessionComponent;

class SessionComponentTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $this->Session = new SessionComponent(new Controller(new Request(), new Response()));
    }
    public function testWrite()
    {
        $this->Session->write('foo', 'bar');
        $this->assertEquals('bar', $_SESSION['foo']);
        $this->assertTrue($this->Session->exists('foo'));
    }
    public function testClear()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->exists('foo'));
        $this->Session->clear();
        $this->assertFalse($this->Session->exists('foo'));
    }
    public function testExists()
    {
        $this->assertFalse($this->Session->exists('password'));
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->exists('foo'));
    }
    public function testRead()
    {
        $this->Session->write('foo', 'bar');
        $this->assertEquals('bar', $this->Session->read('foo'));
    }
    public function testDelete()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->exists('foo'));

        $this->Session->delete('foo');
        $this->assertFalse($this->Session->exists('foo'));
    }

    public function testdestroy()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->exists('foo'));
        $this->Session->destroy();
        $this->assertFalse($this->Session->exists('foo'));
    }
}
