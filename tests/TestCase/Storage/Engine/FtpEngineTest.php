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

namespace Origin\Test\Storage\Engine;

use Origin\Exception\Exception;
use Origin\Storage\Engine\FtpEngine;
use Origin\Exception\InvalidArgumentException;

include_once 'EngineTestTrait.php'; // @todo recreate test with providers maybe

class FtpEngineTest extends \PHPUnit\Framework\TestCase
{
    use EngineTestTrait;

    protected function setUp(): void
    {
        if (! env('FTP_HOST')) {
            $this->markTestSkipped('FTP env vars not set');
        }
    }

    protected $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine = new FtpEngine([
                'host' => env('FTP_HOST'),
                'username' => env('FTP_USERNAME'),
                'password' => env('FTP_PASSWORD'),
            ]);
        }

        return $this->engine;
    }

    public function testInvalidRoot()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new FtpEngine([
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),
            'root' => '/some-directory/that-does-not-exist',
        ]);
    }

    public function testConfig()
    {
        $config = $this->engine()->config();
        $this->assertEquals(env('FTP_HOST'), $config['host']);
        $this->assertEquals(env('FTP_USERNAME'), $config['username']);
        $this->assertEquals(env('FTP_PASSWORD'), $config['password']);

        $this->assertNotEmpty($config['host']);
        $this->assertNotEmpty($config['username']);
        $this->assertNotEmpty($config['password']);
        $this->assertEquals(21, $config['port']);
        $this->assertNotEmpty($config['root']);
        $this->assertEquals(10, $config['timeout']);
        $this->assertFalse($config['ssl']);
        $this->assertTrue($config['passive']);
    }

    public function testNoHostSetException()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new FtpEngine([]);
    }

    /**
     * This is just to test that no errors when called
     *
     * @return void
     */
    public function testErrorConnectingTo()
    {
        $this->expectException(Exception::class);
        $engine = new FtpEngine([
            'host' => '192.168.1.1',
            'username' => 'username',
            'password' => 'password',
            'ssl' => true,
        ]);
    }

    public function testInvalidUsernamePassword()
    {
        $this->expectException(Exception::class);
        $engine = new FtpEngine([
            'host' => env('FTP_HOST'),
            'username' => 'admin',
            'password' => 1234,
        ]);
    }
}
