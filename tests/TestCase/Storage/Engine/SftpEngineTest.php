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

use Origin\Storage\Engine\SftpEngine;

include_once 'EngineTestTrait.php'; // @todo recreate test with providers maybe
use Origin\TestSuite\TestTrait;

use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;
use Origin\Exception\NotFoundException;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;

class MockSftpEngine extends SftpEngine
{
    use TestTrait;
    public function initialize(array $config)
    {
        // dont do anthing
    }

    public function start()
    {
        parent::initialize($this->config());
    }
}

class SftpEngineTest extends \PHPUnit\Framework\TestCase
{
    use EngineTestTrait;
    protected function setUp(): void
    {
        if (!env('SFTP_USERNAME')) {
            $this->markTestSkipped('SFTP env vars not set');
        }
        if (!class_exists(SFTP::class)) {
            $this->markTestSkipped('phpseclib not installed.');
        }
    }

    public $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine =  new SftpEngine([
                'host' => env('SFTP_HOST'),
                'username' => env('SFTP_USERNAME'),
                'password' => env('SFTP_PASSWORD')
            ]);
        }
        return $this->engine;
    }
    public function testConfig()
    {
        $config = $this->engine()->config();

        $this->assertNotEmpty($config['host']);
        $this->assertNotEmpty($config['username']);
        $this->assertNotEmpty($config['password']);
        $this->assertEquals(22, $config['port']);
        $this->assertNotEmpty($config['root']);
        $this->assertEquals(10, $config['timeout']);
    }

    public function testNotFoundPrivateKey()
    {
        $this->expectException(NotFoundException::class);
        $engine = new SftpEngine([
            'host' => env('FTP_HOST'),
            'username' => 'username',
            'password' => 'password',
            'privateKey' => '/somewhere/privateKey'
        ]);
    }

    public function testPrivateKey()
    {
        $rsa = new RSA();
        $pair = $rsa->createKey();
        
        $engine = new MockSftpEngine([
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),
            'privateKey' => $pair['privatekey']
        ]);
        $rsa = $engine->callMethod('loadPrivatekey');
        $this->assertEquals('phpseclib-generated-key', $rsa->comment);
        $this->assertEquals(env('SFTP_PASSWORD'), $rsa->password);
    }


    public function testPrivateKeyFile()
    {
        $rsa = new RSA();
        $pair = $rsa->createKey();
        $tmp = sys_get_temp_dir() . uniqid();
        file_put_contents($tmp, $pair['privatekey']);
        $engine = new MockSftpEngine([
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),
            'privateKey' => $tmp
        ]);
        $rsa = $engine->callMethod('loadPrivatekey');
        $this->assertEquals('phpseclib-generated-key', $rsa->comment);
        $this->assertEquals(env('SFTP_PASSWORD'), $rsa->password);
    }

    public function testNoHostSetException()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new SftpEngine([]);
    }

    /**
     * This is just to test that no errors when called
     *
     * @return void
     */
    public function testErrorConnectingTo()
    {
        $this->expectException(Exception::class);
        $engine = new SftpEngine([
            'host' => 'www.originphp.com',
            'username' => 'foo',
            'password' => 'bar',
            'port' => 401, // invalid port
            'ssl' => true
        ]);
    }

    public function testInvalidUsernamePassword()
    {
        $this->expectException(Exception::class);
        $engine = new SftpEngine([
            'host' => env('FTP_HOST'),
            'username' => 'admin',
            'password' => 1234
        ]);
    }

    public function testInvalidUsernamePasswordPrivateKey()
    {
        $this->expectException(Exception::class);

        $rsa = new RSA();
        $pair = $rsa->createKey();
        $engine = new SftpEngine([
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),
            'privateKey' => $pair['privatekey']
        ]);
    }
}
