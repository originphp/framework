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

use Origin\Engine\Cache\FileEngine;
use Origin\Exception\Exception;

class FileEngineTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $cache = new FileEngine();
        $cache->clear();
    }
    public function testSet()
    {
        $cache = new FileEngine();
        $this->assertTrue($cache->set('foo', 'bar'));
        $this->assertEquals('bar', unserialize(file_get_contents(TMP .'/cache/cache_foo')));
    }
    /**
     * @depends testSet
     */
    public function testGet()
    {
        $cache = new FileEngine();
        $this->assertFalse($cache->get('foo'));
        $cache->set('foo', 'bar');
        $this->assertEquals('bar', $cache->get('foo'));
    }
    /**
     * @depends testSet
     */
    public function testHas()
    {
        $cache = new FileEngine();
        $this->assertFalse($cache->has('foo'));
        $cache->set('foo', 'bar');
        $this->assertTrue($cache->has('foo'));
    }
    /**
     * @depends testHas
     */
    public function testDelete()
    {
        $cache = new FileEngine();
        $cache->set('foo', 'bar');
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->delete('foo'));
        
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->delete('foo'));
    }
    public function testClear()
    {
        $cache = new FileEngine();
        $cache->set('foo', 'bar');
        $cache->set('bar', 'foo');
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
    }
    public function testIncrement()
    {
        $cache = new FileEngine();
        $this->expectException(Exception::class);
        $cache->increment('counter');
    }
    public function testDecrement()
    {
        $cache = new FileEngine();
        $this->expectException(Exception::class);
        $cache->decrement('counter', 9);
    }
}
