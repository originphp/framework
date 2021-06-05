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
declare(strict_types=1);
namespace Origin\Test\Ssh;

use Origin\Ssh\Ssh;
use Origin\Ssh\RemoteFile;
use InvalidArgumentException;

class SshTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('ssh2') || ! env('SSH_HOST') || ! env('SSH_USERNAME') || ! env('SSH_PASSWORD')) {
            $this->markTestSkipped();
        }
    }

    private function getSsh(): Ssh
    {
        return new Ssh([
            'host' => env('SSH_HOST'),
            'username' => env('SSH_USERNAME'),
            'password' => env('SSH_PASSWORD')
        ]);
    }

    /**
     * Basis for tests
     *
     * @return String
     */
    private function getHomePath(): String
    {
        return '/home/' . env('SSH_USERNAME');
    }

    public function testConnectPasswordAuth()
    {
        $ssh = $this->getSsh();
        
        $this->assertTrue($ssh->isConnected());
    }

    public function testConnectPrivateKey()
    {
        if (! env('SSH_PRIVATE_KEY') || ! file_exists(env('SSH_PRIVATE_KEY'))) {
            $this->markTestSkipped();
        }
        $ssh = new Ssh([
            'host' => env('SSH_HOST'),
            'username' => env('SSH_USERNAME'),
            'password' => null,
            'privateKey' => env('SSH_PRIVATE_KEY')
        ]);

        $this->assertTrue($ssh->isConnected());
    }

    public function testDisconnect()
    {
        $ssh = $this->getSsh();
        
        $this->assertTrue($ssh->isConnected());

        $this->assertTrue($ssh->disconnect());
        $this->assertFalse($ssh->isConnected());

        $this->assertFalse($ssh->disconnect());
    }

    public function testSend()
    {
        $ssh = $this->getSsh();
       
        $this->assertTrue(
            $ssh->send(__DIR__ .'/SshTest.php', $this->getHomePath() .'/SshTest/SshTest.php')
        ) ;

        $this->expectException(InvalidArgumentException::class);
        $ssh->send('/somewhere/does-not-exist', $this->getHomePath() .'/null');
    }

    public function testList()
    {
        $ssh = $this->getSsh();
        $list = $ssh->list($this->getHomePath() .'/SshTest', ['recursive' => true]);

        $this->assertNotEmpty($list);

        $file = $list[0];

        $this->assertInstanceOf(RemoteFile::class, $file);

        $this->assertEquals('SshTest.php', $file->name);
        $this->assertEquals($this->getHomePath() .'/SshTest', $file->directory);
        $this->assertEquals($this->getHomePath() .'/SshTest/SshTest.php', $file->path);

        $this->assertEquals('php', $file->extension);
        $this->assertEquals(filesize(__FILE__), $file->size);
        $this->assertGreaterThan(time() - 60, $file->timestamp);
    }

    public function testReceive()
    {
        $ssh = $this->getSsh();
       
        $this->assertTrue(
            $ssh->receive($this->getHomePath() .'/SshTest/SshTest.php', TMP .'/Download.php')
        );

        $this->assertFileExists(TMP .'/Download.php');
    }

    public function testExecuteError()
    {
        $ssh = $this->getSsh();
        $this->assertNull($ssh->getErrorOutput());

        $this->assertFalse($ssh->execute('foo'));
        $this->assertStringContainsString('bash: foo: command not found', $ssh->getErrorOutput());
    }

    public function testExecute()
    {
        $ssh = $this->getSsh();
        $this->assertNull($ssh->getOutput());
        
        $this->assertTrue($ssh->execute('pwd'));
        $this->assertStringContainsString($this->getHomePath(), $ssh->getOutput());
    }
}
