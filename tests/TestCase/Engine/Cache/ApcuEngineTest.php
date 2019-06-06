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
 * @link       https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Engine\Cache;

use Origin\Engine\Cache\ApcuEngine;

class ApcuEngineTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        if (!extension_loaded('apcu')) {
            $this->markTestSkipped('Apcu extension not loaded');
        }

        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped('apc.enable_cli disabled');
        }

        apcu_clear_cache();
    }

    /**
     * Creates a the cache engine and initlaizes it.
     */
    public function engine()
    {
        $engine = new ApcuEngine([
            'duration' => 3600,
            'prefix' => 'origin_',
        ]);

        return $engine;
    }

    public function testSet()
    {
        $cache = $this->engine();
        $this->assertTrue($cache->write('foo', 'bar'));
        $this->assertEquals('bar', apcu_fetch('origin_foo'));
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        $cache = $this->engine();
        $this->assertFalse($cache->read('foo'));
        $cache->write('foo', 'bar');
        $this->assertEquals('bar', $cache->read('foo'));
    }

    /**
     * @depends testSet
     */
    public function testHas()
    {
        $cache = $this->engine();
        $this->assertFalse($cache->exists('foo'));
        $cache->write('foo', 'bar');
        $this->assertTrue($cache->exists('foo'));
    }

    /**
     * @depends testHas
     */
    public function testDelete()
    {
        $cache = $this->engine();
        $cache->write('foo', 'bar');
        $this->assertTrue($cache->exists('foo'));
        $this->assertTrue($cache->delete('foo'));

        $this->assertFalse($cache->exists('foo'));
        $this->assertFalse($cache->delete('foo'));
    }

    public function testClear()
    {
        $cache = $this->engine();
        $cache->write('foo', 'bar');
        $cache->write('bar', 'foo');
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->exists('foo'));
        $this->assertFalse($cache->exists('bar'));
    }

    public function testIncrement()
    {
        $cache = $this->engine();
        $cache->write('counter', 100);
        $this->assertEquals(101, $cache->increment('counter'));
        $this->assertEquals(110, $cache->increment('counter', 9));
    }

    public function testDecrement()
    {
        $cache = $this->engine();
        $cache->write('counter', 110);
        $this->assertEquals(109, $cache->decrement('counter'));
        $this->assertEquals(100, $cache->decrement('counter', 9));
    }

    public function tearDown()
    {
        apcu_clear_cache();
    }
}
