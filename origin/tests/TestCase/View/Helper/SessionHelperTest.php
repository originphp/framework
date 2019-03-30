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
use Origin\View\Helper\SessionHelper;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Core\Session;

class MockSessionHelper extends SessionHelper
{
    public function getSession()
    {
        return $this->session();
    }
}

class SessionHelperTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
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
    public function testCheck()
    {
        $this->Session->write('foo', 'bar');
        $this->assertTrue($this->Session->check('foo'));
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
