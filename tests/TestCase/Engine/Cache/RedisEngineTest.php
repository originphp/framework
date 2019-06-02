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

namespace Origin\Test\Engine\Cache;

use Origin\Engine\Cache\RedisEngine;
use Redis;
use Origin\Exception\Exception;

class MockRedisEngine extends RedisEngine
{
    public function redis()
    {
        return $this->Redis;
    }
}

class RedisEngineTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded');
        }
     
        $cache = $this->engine();
        $cache->clear();
    }

    /**
     * Creates a the cache engine and initlaizes it
     *
     * @return void
     */
    public function engine()
    {
        return new MockRedisEngine([
            'host' => 'redis',
            'duration' => 3600,
            'prefix' => 'origin_'
        ]);
    }
    public function testSet()
    {
        $cache = $this->engine();
        $this->assertTrue($cache->set('foo', 'bar'));
        $this->assertEquals('bar', $cache->redis()->get('origin_foo'));
    }
    /**
     * @depends testSet
     */
    public function testGet()
    {
        $cache = $this->engine();
        $this->assertFalse($cache->get('foo'));
        $cache->set('foo', 'bar');
        $this->assertEquals('bar', $cache->get('foo'));
    }
    /**
     * @depends testSet
     */
    public function testHas()
    {
        $cache = $this->engine();
        $this->assertFalse($cache->has('foo'));
        $cache->set('foo', 'bar');
        $this->assertTrue($cache->has('foo'));
    }
    /**
     * @depends testHas
     */
    public function testDelete()
    {
        $cache = $this->engine();
        $cache->set('foo', 'bar');
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->delete('foo'));
        
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->delete('foo'));
    }
    /**
     * @depends testSet
     */
    public function testClear()
    {
        $cache = $this->engine();
        $cache->set('foo', 'bar');
        $cache->set('bar', 'foo');
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
    }
    /**
     * @depends testSet
     */
    public function testIncrement()
    {
        $cache = $this->engine();
        $cache->set('counter', 100);
        $this->assertEquals(101, $cache->increment('counter'));
        $this->assertEquals(110, $cache->increment('counter', 9));
    }
    /**
     * @depends testSet
     */
    public function testDecrement()
    {
        $cache = $this->engine();
        $cache->set('counter', 110);
        $this->assertEquals(109, $cache->decrement('counter'));
        $this->assertEquals(100, $cache->decrement('counter', 9));
    }

    public function tearDown()
    {
        $cache = $this->engine();
        $cache->clear();
    }

    
    /**
     * Make sure it runs smothely
     *
     * @return void
     */
    public function testPersistent()
    {
        $redis = new MockRedisEngine([
            'host' => 'redis',
            'duration' => 0,
            'prefix' => 'origin_',
            'persistent' => 'persisten-id'
        ]);
    
        $redis ->set('counter', 100);
        $this->assertEquals(101, $redis->increment('counter'));
    }
}
