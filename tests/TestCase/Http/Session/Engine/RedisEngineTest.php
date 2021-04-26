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

use Origin\Redis\Redis;
use Origin\Security\Security;
use Origin\Http\Session\Engine\RedisEngine;

class RedisSession extends RedisEngine
{
    public function getHash()
    {
        return $this->hash;
    }
}

class RedisEngineTest extends \PHPUnit\Framework\TestCase
{
    public static function setupBeforeClass(): void
    {
        Redis::config('session', [
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
        ]);
    }

    public function testStart()
    {
        $session = new RedisSession();
        $this->assertFalse($session->started());
        $this->assertTrue($session->start());
        $this->assertTrue($session->started());
        $this->assertFalse($session->start());
    }

    public function testId()
    {
        $session = new RedisSession();
        $this->assertNull($session->id());
        $session->id('abc123');
        $this->assertEquals('abc123', $session->id());

        $session->start();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}+$/', $session->id());
    }

    public function testName()
    {
        $session = new RedisSession();
        $this->assertEquals('id', $session->name());
        $session->name('foo');
        $this->assertEquals('foo', $session->name());
    }

    public function testGet()
    {
        // Create Session and test data
        $data = Security::hex();
        $session = new RedisSession();
        $this->assertTrue($session->start());

        $session->getHash()->set('foo', $data);

        $this->assertEquals($data, $session->read('foo'));
        $this->assertNull($session->read('nothing'));
        $this->assertEquals('1234', $session->read('nothing', '1234'));
    }

    public function testSet()
    {
        // Create Session and test data
        $data = Security::hex();
        $session = new RedisSession();
        $this->assertTrue($session->start());

        $session->write('foo', $data);

        $this->assertEquals($data, $session->getHash()->items()['foo']);
    }

    public function testHas()
    {
        $session = new RedisSession();
        $this->assertTrue($session->start());

        $this->assertFalse($session->exists('foo'));
        $session->write('foo', 'bar');
        $this->assertTrue($session->exists('foo'));
    }

    public function testDelete()
    {
        $session = new RedisSession();
        $this->assertTrue($session->start());
        $session->write('foo', 'bar');

        $this->assertTrue($session->exists('foo'));
        $this->assertTrue($session->delete('foo'));

        $this->assertFalse($session->exists('foo'));
        $this->assertFalse($session->delete('foo'));
    }

    public function testClose()
    {
        $session = new RedisSession();
        $this->assertTrue($session->start());

        $this->assertTrue($session->close());
        $this->assertFalse($session->started());
    }

    public function testToArray()
    {
        $session = new RedisSession();
        $this->assertTrue($session->start());
        $this->assertTrue($session->delete('Session')); // Remove timeout

        $this->assertEquals([], $session->toArray());

        $session->write('foo', 'bar');
        $this->assertEquals(['foo' => 'bar'], $session->toArray());
    }

    /**
     * Sanity checks
     *
     * @return void
     */
    public function testDotNotation()
    {
        $session = new RedisSession();
        $this->assertTrue($session->start());

        // Test write
        $session->write('Something.value', 'foo');
        $session->write('Another', ['key' => 'value']);

        // Test read
        $this->assertEquals('foo', $session->read('Something.value'));
        $this->assertEquals(['value' => 'foo'], $session->read('Something'));
        $this->assertNull($session->read('Something.else'));

        // Test has
        $this->assertTrue($session->exists('Something'));
        $this->assertTrue($session->exists('Something.value'));
        $this->assertFalse($session->exists('Something.else'));

        // Test Delete
        $this->assertTrue($session->delete('Something.value'));
        $this->assertFalse($session->exists('Something.value'));
        $this->assertTrue($session->exists('Something'));

        $this->assertTrue($session->delete('Another'));
        $this->assertFalse($session->exists('Another'));
    }

    /**
     * @depends testToArray
     */
    public function testClear()
    {
        $session = new RedisSession();
        $this->assertTrue($session->start());
        $session->write('foo', 'bar');
        $session->clear();
        
        $this->assertEquals([], $session->toArray());
    }

    public function testDestroy()
    {
        $session = new RedisSession();
        $this->assertTrue($session->start());
        $session->write('foo', 'bar');

        $session->destroy();
        $this->assertFalse($session->started());
        $this->assertNull($session->id());
        $this->assertEquals([], $session->toArray());
    }

    public function testTimedout()
    {
        $session = new RedisSession();
        $session->write('foo', 'bar');
        $this->assertTrue($session->start());
        $this->assertTrue($session->exists('foo'));
        
        $session->write('Session.lastActivity', strtotime('2021/01/01 12:00:00')); // required
        $session->close(); // save to session
       
        $this->assertTrue($session->start());
        $this->assertFalse($session->exists('foo'));
    }
}
