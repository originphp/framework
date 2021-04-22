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
namespace Origin\Test\TestCase\Redis;

use Origin\Redis\Redis;
use Origin\Redis\Connection;
use InvalidArgumentException;

class RedisTest extends \PHPUnit\Framework\TestCase
{
    private static $defaultConfig = [];

    public static function setupBeforeClass(): void
    {
        static::$defaultConfig = Redis::config('default');

        Redis::config([
            'default' => [
                'host' => env('REDIS_HOST'),
                'port' => (int) env('REDIS_PORT'),
            ]
        ]);
    }

    protected function setUp(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded');
        }
        if (! getenv('REDIS_HOST') || ! getenv('REDIS_PORT')) {
            $this->markTestSkipped('Redis settings not found');
        }
    }

    public function testDefaultConfig()
    {
        $expected = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'path' => null,
            'persistent' => false,
            'timeout' => 0,
            'database' => null,
            'prefix' => null
        ];

        $this->assertSame($expected, static::$defaultConfig);
    }

    public function testUnkownConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        Redis::connection('foo');
    }

    public function testConnection()
    {
        Redis::config('test', [
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
        ]);

        $this->assertInstanceOf(Connection::class, Redis::connection('test'));
    }

    public function testClient()
    {
        $this->assertInstanceOf(\Redis::class, Redis::connection('test')->client());
    }

    public function testSet()
    {
        $this->assertTrue(Redis::set('foo', 'bar'));
    }

    public function testGet()
    {
        $this->assertEquals('bar', Redis::get('foo'));
        $this->assertNull(Redis::get('does-not-exist'));
        $this->assertEquals('default', Redis::get('does-not-exist', 'default'));
    }

    public function testExists()
    {
        $this->assertTrue(Redis::exists('foo'));
        $this->assertFalse(Redis::exists('does-not-exist'));
    }

    public function testDelete()
    {
        $this->assertTrue(Redis::delete('foo'));
        $this->assertFalse(Redis::delete('does-not-exist'));
    }

    public function testIncrement()
    {
        Redis::set('counter', 5);
        $this->assertEquals(6, Redis::increment('counter'));
        $this->assertEquals(10, Redis::increment('counter', 4));

        $this->assertEquals(1, Redis::increment('unkown-increment'));
    }

    public function testDecrement()
    {
        Redis::set('counter', 5);
        $this->assertEquals(4, Redis::decrement('counter'));
        $this->assertEquals(1, Redis::decrement('counter', 3));
        $this->assertEquals(-4, Redis::decrement('counter', 5));

        $this->assertEquals(-1, Redis::decrement('unkown-decrement'));
    }

    public function testClear()
    {
        $this->assertTrue(Redis::exists('counter'));
        Redis::flush();
        $this->assertFalse(Redis::exists('counter'));
    }

    public function testKeys()
    {
        $this->assertEquals([], Redis::keys());

        Redis::set('foo', 'bar');
        $this->assertEquals(['foo'], Redis::keys());
    }
}
