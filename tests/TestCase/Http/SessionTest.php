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

namespace Origin\Test\Http;

use Origin\Http\Session;
use Origin\TestSuite\TestTrait;

class MockSession extends Session
{
    use TestTrait;
}

class SessionTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Session = new Session();
    }
 
    public function testWrite()
    {
        $this->Session->write('sessionTest', 'works');
        $this->assertTrue(isset($_SESSION['sessionTest']));
        $this->assertEquals('works', $_SESSION['sessionTest']);
    }

    public function testRead()
    {
        $this->assertNull($this->Session->read('sessionTest'));

        $this->Session->write('sessionTest', 'works');

        $this->assertEquals('works', $this->Session->read('sessionTest'));
        
        $this->Session->write('Test.status', 'ok');
        $this->assertEquals('ok', $this->Session->read('Test.status'));
    }

    public function testExists()
    {
        $this->Session->write('Test.status', 'ok');
        $this->assertTrue($this->Session->exists('Test.status'));
        $this->assertFalse($this->Session->exists('Test.password'));
    }

    public function testDelete()
    {
        $this->Session->write('Test.status', 'ok');
        $this->assertTrue($this->Session->delete('Test.status'));
        $this->assertFalse($this->Session->delete('Test.password'));
    }

    public function testDestroy()
    {
        $this->Session->write('Test.status', 'ok');

        $this->assertTrue($this->Session->started());
        $this->Session->destroy();
        $this->assertFalse($this->Session->exists('Test.status'));
    }

   
    /**
     * @depends testDestroy
     */
    public function testCreate()
    {
        $this->Session->destroy();
        $this->Session->start();
        $this->Session->write('Test.status', 'ok');
        $this->assertTrue($this->Session->exists('Test.status'));
    }

    public function testClear()
    {
        $this->Session->write('Test.status', 'ok');
        $this->assertNotEmpty($_SESSION);
        $this->Session->clear();
        $this->assertEmpty($_SESSION);
    }
    public function testValidate()
    {
        $session = new MockSession();
        $name = session_name();
        $_COOKIE[$name] = uuid();
        $id = $session->callMethod('validateCookie');
        $this->assertEquals(36, strlen($id));
        unset($_COOKIE[$name]);
        
        $session = new MockSession();
        $_COOKIE[$name] = base64_encode(md5('originPHP'));
        $this->assertNull($session->callMethod('validateCookie'));
        unset($_COOKIE[$name]);

        $session = new MockSession();
        $_COOKIE[$name] = bin2hex(random_bytes(1024));
        $this->assertNull($session->callMethod('validateCookie'));
        unset($_COOKIE[$name]);
    }

    public function testTimedout()
    {
        $session = new MockSession();
        $session->write('Session.lastActivity', time());
        $this->assertFalse($session->callMethod('timedOut'));
        $session->write('Session.lastActivity', strtotime('-3601 seconds'));
        $this->assertTrue($session->callMethod('timedOut'));
    }
}
