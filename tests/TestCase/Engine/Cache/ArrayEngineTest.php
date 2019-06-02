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

use Origin\Engine\Cache\ArrayEngine;
use Origin\TestSuite\TestTrait;

class MockArrayEngine extends ArrayEngine
{
    use TestTrait;
}

class ArrayEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $cache = new MockArrayEngine();
        $this->assertTrue($cache->set('foo', 'bar'));
        $this->assertArrayHasKey('foo', $cache->getProperty('data'));
    }
    /**
     * @depends testSet
     */
    public function testGet()
    {
        $cache = new ArrayEngine();
        $this->assertFalse($cache->get('foo'));
        $cache->set('foo', 'bar');
        $this->assertEquals('bar', $cache->get('foo'));
    }
    /**
     * @depends testSet
     */
    public function testHas()
    {
        $cache = new ArrayEngine();
        $this->assertFalse($cache->has('foo'));
        $cache->set('foo', 'bar');
        $this->assertTrue($cache->has('foo'));
    }
    /**
     * @depends testHas
     */
    public function testDelete()
    {
        $cache = new ArrayEngine();
        $cache->set('foo', 'bar');
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->delete('foo'));
        
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->delete('foo'));
    }
    public function testClear()
    {
        $cache = new ArrayEngine();
        $cache->set('foo', 'bar');
        $cache->set('bar', 'foo');
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
    }
    public function testIncrement()
    {
        $cache = new ArrayEngine();
        $this->assertEquals(1, $cache->increment('counter'));
        $cache->set('counter', 100);
        $this->assertEquals(101, $cache->increment('counter'));
        $this->assertEquals(110, $cache->increment('counter', 9));
    }
    public function testDecrement()
    {
        $cache = new ArrayEngine();
        $this->assertEquals(-1, $cache->decrement('counter'));
        $cache->set('counter', 110);
        $this->assertEquals(109, $cache->decrement('counter'));
        $this->assertEquals(100, $cache->decrement('counter', 9));
    }
}
