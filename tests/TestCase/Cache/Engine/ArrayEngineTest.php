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

namespace Origin\Test\Cache\Engine;

use Origin\TestSuite\TestTrait;
use Origin\Cache\Engine\ArrayEngine;

class MockArrayEngine extends ArrayEngine
{
    use TestTrait;
}

class ArrayEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $cache = new MockArrayEngine();
        $this->assertTrue($cache->write('foo', 'bar'));
        $this->assertArrayHasKey('foo', $cache->getProperty('data'));
    }
    /**
     * @depends testSet
     */
    public function testGet()
    {
        $cache = new ArrayEngine();
        $this->assertFalse($cache->read('foo'));
        $cache->write('foo', 'bar');
        $this->assertEquals('bar', $cache->read('foo'));
    }
    /**
     * @depends testSet
     */
    public function testHas()
    {
        $cache = new ArrayEngine();
        $this->assertFalse($cache->exists('foo'));
        $cache->write('foo', 'bar');
        $this->assertTrue($cache->exists('foo'));
    }
    /**
     * @depends testHas
     */
    public function testDelete()
    {
        $cache = new ArrayEngine();
        $cache->write('foo', 'bar');
        $this->assertTrue($cache->exists('foo'));
        $this->assertTrue($cache->delete('foo'));
        
        $this->assertFalse($cache->exists('foo'));
        $this->assertFalse($cache->delete('foo'));
    }
    public function testClear()
    {
        $cache = new ArrayEngine();
        $cache->write('foo', 'bar');
        $cache->write('bar', 'foo');
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->exists('foo'));
        $this->assertFalse($cache->exists('bar'));
    }
    public function testIncrement()
    {
        $cache = new ArrayEngine();
        $this->assertEquals(1, $cache->increment('counter'));
        $cache->write('counter', 100);
        $this->assertEquals(101, $cache->increment('counter'));
        $this->assertEquals(110, $cache->increment('counter', 9));
    }
    public function testDecrement()
    {
        $cache = new ArrayEngine();
        $this->assertEquals(-1, $cache->decrement('counter'));
        $cache->write('counter', 110);
        $this->assertEquals(109, $cache->decrement('counter'));
        $this->assertEquals(100, $cache->decrement('counter', 9));
    }
}
