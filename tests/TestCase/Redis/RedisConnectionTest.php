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

use Origin\Redis\RedisConnection;
use Redis;

class RedisConnectionTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded');
        }
        if (! getenv('REDIS_HOST') or ! getenv('REDIS_PORT')) {
            $this->markTestSkipped('Redis settings not found');
        }
    }
    
    /**
     * Make sure it runs smothely
     *
     * @return void
     */
    public function testPersistent()
    {
        $result = RedisConnection::connect([
            'host' => getenv('REDIS_HOST'),
            'duration' => 0,
            'prefix' => 'origin_',
            'persistent' => 'persisten-id',
        ]);
        $this->assertInstanceOf(Redis::class, $result);
    }
    public function testSocketException()
    {
        $this->expectException(Exception::class);
        RedisConnection::connect([
            'engine' => 'Redis',
            'path' => '/var/sockets/redis',
        ]);
    }
    public function testNonPersisentException()
    {
        $this->expectException(Exception::class);
        RedisConnection::connect([
            'host' => 'foo',
            'port' => 1234,
        ]);
    }
    public function testInvalidPassword()
    {
        $this->expectException(Exception::class);
        RedisConnection::connect([
            'host' => getenv('REDIS_HOST'),
            'port' => (int) getenv('REDIS_PORT'),
            'password' => 'secret',
        ]);
    }
}
