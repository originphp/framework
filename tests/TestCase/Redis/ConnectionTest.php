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

use RuntimeException;
use Origin\Redis\Redis;
use Origin\Redis\Connection;

class ConnectionTest extends \PHPUnit\Framework\TestCase
{
    public static function setupBeforeClass(): void
    {
        Redis::config('test', [
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
        ]);
    }

    public function testPersistent()
    {
        $redis = new Connection([
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
            'timeout' => 0,
            'persistent' => 'persisten-id',
        ]);

        $this->assertInstanceOf(\Redis::class, $redis->client());
    }

    public function testSocketException()
    {
        $this->expectException(RuntimeException::class);
        $redis = new Connection([
            'port' => (int) env('REDIS_PORT'),
            'timeout' => 0,
            'path' => '/var/sockets/redis',
        ]);
    }

    public function testNonPersisentException()
    {
        $this->expectException(RuntimeException::class);
        $redis = new Connection([
            'host' => 'foo',
            'port' => 1234,
            'timeout' => 0,
        ]);
    }

    public function testInvalidPassword()
    {
        $this->expectException(RuntimeException::class);
        $redis = new Connection([
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
            'timeout' => 0,
            'password' => 'secret',
        ]);
    }

    public function testClient()
    {
        $this->assertInstanceOf(\Redis::class, Redis::connection('test')->client());
    }

    public function testSet()
    {
        $connection = Redis::connection('test');
        $this->assertTrue($connection->set('foo', 'bar'));

        $this->assertTrue($connection->set('bar', 'foo', ['duration' => 1]));
    }

    public function testGet()
    {
        $connection = Redis::connection('test');
        $this->assertEquals('bar', $connection->get('foo'));
        $this->assertNull($connection->get('does-not-exist'));
        $this->assertEquals('default', $connection->get('does-not-exist', 'default'));
    }

    public function testExists()
    {
        $connection = Redis::connection('test');
        $this->assertTrue($connection->exists('foo'));
        $this->assertFalse($connection->exists('does-not-exist'));
    }

    public function testSetExpired()
    {
        $connection = Redis::connection('test');

        $this->assertTrue($connection->exists('bar'));
        sleep(1);
        $this->assertFalse($connection->exists('bar'));
    }

    public function testDelete()
    {
        $connection = Redis::connection('test');
        $this->assertTrue($connection->delete('foo'));
        $this->assertFalse($connection->delete('does-not-exist'));
    }

    public function testIncrement()
    {
        $connection = Redis::connection('test');
        $connection->set('counter', 5);
        $this->assertEquals(6, $connection->increment('counter'));
        $this->assertEquals(10, $connection->increment('counter', 4));

        $this->assertEquals(1, $connection->increment('unkown-increment'));
    }

    public function testDecrement()
    {
        $connection = Redis::connection('test');
        $connection->set('counter', 5);
        $this->assertEquals(4, $connection->decrement('counter'));
        $this->assertEquals(1, $connection->decrement('counter', 3));
        $this->assertEquals(-4, $connection->decrement('counter', 5));

        $this->assertEquals(-1, $connection->decrement('unkown-decrement'));
    }

    public function testClear()
    {
        $connection = Redis::connection('test');
        $this->assertTrue($connection->exists('counter'));
        $connection->flush();
        $this->assertFalse($connection->exists('counter'));
    }

    public function testKeys()
    {
        $connection = Redis::connection('test');
        $this->assertEquals([], $connection->keys());

        $connection->set('foo', 'bar');
        $this->assertEquals(['foo'], $connection->keys());
    }

    public function testSelectDatabase()
    {
        $redis = new Connection([
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
            'timeout' => 0,
            'database' => 1
        ]);
        $this->assertTrue($redis->set('abc', true));

        $this->assertFalse(Redis::connection('test')->exists('abc'));
        $this->assertTrue($redis->exists('abc'));
    }

    public function testPrefix()
    {
        $redis = new Connection([
            'host' => env('REDIS_HOST'),
            'port' => (int) env('REDIS_PORT'),
            'timeout' => 0,
            'prefix' => 'demo_'
        ]);

        $this->assertTrue($redis->set('foo', 'barr'));
        $this->assertTrue($redis->exists('foo'));
        $this->assertContains('demo_foo', $redis->keys());
    }
}
