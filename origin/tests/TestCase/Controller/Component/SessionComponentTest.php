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
use Origin\Controller\Component\SessionComponent;
use Origin\Controller\Request;
use Origin\Controller\Response;

class SessionComponentTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $_SESSION = [];
        $this->Session = new SessionComponent(new Controller(new Request(), new Response()));
    }
    public function testWrite()
    {
        $this->Session->write('foo', 'bar');
        $this->assertEquals('bar', $this->Session->request()->session()->read('foo'));
    }
    public function testCheck()
    {
        $this->assertFalse($this->Session->check('password'));
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->check('foo'));
    }
    public function testRead()
    {
        $this->Session->write('foo', 'bar');
        $this->assertEquals('bar', $this->Session->read('foo'));
    }
    public function testDelete()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->check('foo'));

        $this->Session->delete('foo');
        $this->assertFalse($this->Session->check('foo'));
    }

    public function testdestroy()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->check('foo'));
        $this->Session->destroy();
        $this->assertFalse($this->Session->check('foo'));
    }
}
