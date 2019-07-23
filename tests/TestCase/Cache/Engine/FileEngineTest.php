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

use Origin\Exception\Exception;
use Origin\Cache\Engine\FileEngine;

class FileEngineTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $cache = new FileEngine();
        $cache->clear();
    }
    public function testSet()
    {
        $cache = new FileEngine();
        $this->assertTrue($cache->write('foo', 'bar'));
        $this->assertEquals('bar', unserialize(file_get_contents(TMP .'/cache/origin_foo')));
    }
    /**
     * @depends testSet
     */
    public function testGet()
    {
        $cache = new FileEngine();
        $this->assertFalse($cache->read('foo'));
        $cache->write('foo', 'bar');
        $this->assertEquals('bar', $cache->read('foo'));
    }
    /**
     * @depends testSet
     */
    public function testHas()
    {
        $cache = new FileEngine();
        $this->assertFalse($cache->exists('foo'));
        $cache->write('foo', 'bar');
        $this->assertTrue($cache->exists('foo'));
    }
    /**
     * @depends testHas
     */
    public function testDelete()
    {
        $cache = new FileEngine();
        $cache->write('foo', 'bar');
        $this->assertTrue($cache->exists('foo'));
        $this->assertTrue($cache->delete('foo'));
        
        $this->assertFalse($cache->exists('foo'));
        $this->assertFalse($cache->delete('foo'));
    }
    public function testClear()
    {
        $cache = new FileEngine();
        $cache->write('foo', 'bar');
        $cache->write('bar', 'foo');
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->exists('foo'));
        $this->assertFalse($cache->exists('bar'));
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
