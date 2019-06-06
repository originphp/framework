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

use Origin\Engine\Cache\NullEngine;

class NullEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $cache = new NullEngine();
        $this->assertTrue($cache->write('foo', 'bar'));
    }
    /**
     * @depends testSet
     */
    public function testGet()
    {
        $cache = new NullEngine();
        $cache->write('foo', 'bar');
        $this->assertFalse($cache->read('foo'));
    }
    /**
     * @depends testSet
     */
    public function testHas()
    {
        $cache = new NullEngine();
        $cache->write('foo', 'bar');
        $this->assertFalse($cache->exists('foo'));
    }
    /**
     * @depends testHas
     */
    public function testDelete()
    {
        $cache = new NullEngine();
        $this->assertTrue($cache->delete('foo'));
    }
    public function testClear()
    {
        $cache = new NullEngine();
        $this->assertFalse($cache->clear());
    }
    public function testIncrement()
    {
        $cache = new NullEngine();
        $this->assertTrue($cache->increment('counter'));
    }
    public function testDecrement()
    {
        $cache = new NullEngine();
        $this->assertTrue($cache->decrement('counter'));
    }
}
