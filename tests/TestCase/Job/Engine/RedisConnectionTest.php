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
namespace Origin\Test\Redis;

use Redis;
use Origin\Core\Exception\Exception;
use Origin\TestSuite\OriginTestCase;
use Origin\Job\Engine\RedisConnection;

class RedisConnectionTest extends OriginTestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded');
        }
        if (! getenv('REDIS_HOST') || ! getenv('REDIS_PORT')) {
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
        $this->deprecated(function () {
            $result = RedisConnection::connect([
                'host' => getenv('REDIS_HOST'),
                'port' => (int) getenv('REDIS_PORT'),
                'timeout' => 0,
                'persistent' => 'persisten-id',
            ]);
            $this->assertInstanceOf(Redis::class, $result);
        });
    }
    public function testSocketException()
    {
        $this->expectException(Exception::class);
        $this->deprecated(function () {
            RedisConnection::connect([
                'port' => (int) getenv('REDIS_PORT'),
                'timeout' => 0,
                'path' => '/var/sockets/redis',
            ]);
        });
    }
    public function testNonPersisentException()
    {
        $this->expectException(Exception::class);
        $this->deprecated(function () {
            RedisConnection::connect([
                'host' => 'foo',
                'port' => 1234,
                'timeout' => 0,

            ]);
        });
    }
    public function testInvalidPassword()
    {
        $this->expectException(Exception::class);
        $this->deprecated(function () {
            RedisConnection::connect([
                'host' => getenv('REDIS_HOST'),
                'port' => (int) getenv('REDIS_PORT'),
                'timeout' => 0,
                'password' => 'secret',
            ]);
        });
    }
}
