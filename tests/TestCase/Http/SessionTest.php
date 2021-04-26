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

use Origin\Http\Session;
use Origin\Security\Security;
use Origin\Http\Session\Engine\ArrayEngine;

class SessionTest extends \PHPUnit\Framework\TestCase
{
    public function testSessionStart()
    {
        $session = new Session(new ArrayEngine);
        $this->assertFalse($session->started());
        $session->start();
        $this->assertTrue($session->started());
    }

    public function testSessionId()
    {
        $session = new Session(new ArrayEngine);
        $this->assertNull($session->id());
        $id = uniqid();
        $session->id($id);
        $this->assertEquals($id, $session->id());
    }
  
    public function testGet()
    {
        $session = new Session(new ArrayEngine);
        $this->assertNull($session->read('foo'));
        $this->assertEquals('bar', $session->read('foo', 'bar'));

        $session->write('foo', 'bar');
        $this->assertEquals('bar', $session->read('foo'));
    }

    /**
     * @depends testGet
     */
    public function testSet()
    {
        $session = new Session(new ArrayEngine);
        $session->write('foo', 'bar');
        $this->assertEquals('bar', $session->read('foo'));
    }

    /**
     * @depends testSet
     */
    public function testExists()
    {
        $session = new Session(new ArrayEngine);
        $this->assertFalse($session->exists('foo'));

        $session->write('foo', 'bar');
        $this->assertTrue($session->exists('foo'));
    }

    /**
     * @depends testSet
     */
    public function testDelete()
    {
        $session = new Session(new ArrayEngine);

        $session->write('foo', 'bar');
        $this->assertTrue($session->exists('foo'));
    
        $this->assertTrue($session->delete('foo'));
        $this->assertFalse($session->delete('foo'));
        $this->assertFalse($session->exists('foo'));
    }

    public function testSessionArray()
    {
        $session = new Session(new ArrayEngine);
        $this->assertEquals([], $session->toArray());
        $session->write('foo', 'bar');
        $this->assertEquals(['foo' => 'bar'], $session->toArray());
    }

    public function testClear()
    {
        $session = new Session(new ArrayEngine);
        $session->write('foo', 'bar');
        $this->assertNotEmpty($session->toArray());
        $session->clear();
        $this->assertEmpty($session->toArray());
    }

    public function testDestroy()
    {
        $session = new Session(new ArrayEngine);
        
        // Setup
        $id = Security::hex(32);
        $session->id($id);
        $session->start();
        $session->write('foo', 'bar');

        $this->assertTrue($session->started());
        $this->assertEquals($id, $session->id());
        $this->assertNotEmpty($session->toArray());

        // Test

        $session->destroy();
        $this->assertFalse($session->started());
        $this->assertNull($session->id());
        $this->assertEmpty($session->toArray());
    }
}
