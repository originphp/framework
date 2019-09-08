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

namespace Origin\Test\Utility;

use Origin\Network\Socket;
use Origin\Network\Exception\SocketException;
use Origin\Exception\InvalidArgumentException;
use Origin\Network\Exception\SocketTimeoutException;

class SocketTest extends \PHPUnit\Framework\TestCase
{
    public function testConnection()
    {
        $socket = new Socket([
            'host' => 'example.com','port' => 80,'timeout' => 5
        ]);
        $this->assertTrue($socket->connect());
        $this->assertIsResource($socket->connection());
        $this->assertTrue($socket->disconnect());
    }

    public function testLastError()
    {
        $socket = new Socket();
        $socket->lastError('foo');
        $this->assertEquals('foo', $socket->lastError());
    }

    public function testConnectionException()
    {
        $this->expectException(SocketException::class);
        $socket = new Socket(['host' => 'foo']);
        $socket->connect();
    }

    public function testConnectionExceptionErrorHandler()
    {
        $this->expectException(SocketException::class);
        $socket = new Socket(['host' => 'www.example.com','protocol' => 'ssl','port' => 80]);
        $socket->connect();
    }

    public function testEnableCryptoException()
    {
        $this->expectException(SocketException::class);
        $socket = new Socket([
            'host' => 'example.com','port' => 80,'timeout' => 5
        ]);
        $this->assertTrue($socket->connect());
        $socket->enableCrypto('tls');
    }

    /**
     * By setting verify_peer to false, i know the ssl context is being set
     *
     * Origin\Network\Exception\SocketException: stream_socket_client(): SSL operation failed with code 1. OpenSSL Error messages:
    * error:1416F086:SSL routines:tls_process_server_certificate:certificate verify failed
    * stream_socket_client(): Failed to enable crypto
     * stream_socket_client(): unable to connect to ssl://originphp.com:443 (Unknown error)
     *
     * @return void
     */
    public function testCreateContext()
    {
        $context = [
            'ssl' => [
                'verify_peer' => false
            ]];
      
        $socket = new Socket([
            'host' => 'originphp.com','port' => 443,'protocol' => 'ssl','timeout' => 2,
            'context' => $context
        ]);
        $this->assertTrue($socket->connect());
    }

    public function testSocketTimeout()
    {
        $this->expectException(SocketTimeoutException::class);
        $socket = new Socket([
            'host' => 'example.com','port' => 80,'timeout' => 1
        ]);
        $this->assertTrue($socket->connect());
        $socket->read();
    }

    public function testSocketWrite()
    {
        $socket = new Socket([
            'host' => 'example.com','port' => 80,'timeout' => 5
        ]);
        $this->assertEquals(0, $socket->write('It is not connected'));
        $this->assertTrue($socket->connect());
        $this->assertEquals(58, $socket->write("GET http://www.example.com HTTP/1.1\r\nConnection: close\r\n\r\n"));
        $this->assertTrue($socket->disconnect());
    }

    public function testConnectReConnect()
    {
        $socket = new Socket([
            'host' => 'example.com','port' => 80,'timeout' => 5
        ]);
        $this->assertTrue($socket->connect());
        $this->assertTrue($socket->connect());
        $this->assertEquals(58, $socket->write("GET http://www.example.com HTTP/1.1\r\nConnection: close\r\n\r\n"));
        $this->assertTrue($socket->disconnect());
    }

    /**
     * @depends testSocketWrite
     */
    
    public function testSocketRead()
    {
        $socket = new Socket([
            'host' => 'example.com','port' => 80,'timeout' => 5
        ]);
        $this->assertNull($socket->read());
        $this->assertTrue($socket->connect());
            
        $this->assertEquals(58, $socket->write("GET http://www.example.com HTTP/1.1\r\nConnection: close\r\n\r\n"));
       
        $this->assertStringContainsString('<h1>Example Domain</h1>', $socket->read(2000));
        $this->assertNull($socket->lastError());
        $this->assertTrue($socket->disconnect());
    }

    public function testSocketSSL()
    {
        $socket = new Socket([
            'host' => 'google.com','port' => 443,'protocol' => 'ssl','timeout' => 5
        ]);
        $this->assertTrue($socket->connect());
        //$this->assertTrue($socket->enableCrypto('sslv3'));
        $this->assertEquals(65, $socket->write("GET https://about.google/intl/en/ HTTP/1.1\r\nConnection: close\r\n\r\n"));
        $this->assertStringContainsString('<title>About | Google</title>', $socket->read(2500));
    }

    public function testEnableCrypto()
    {
        $socket = new Socket([
            'host' => 'smtp.gmail.com','port' => 587,'timeout' => 5
        ]);
        $this->assertTrue($socket->connect());
        $this->assertStringContainsString('220 ', $this->wait($socket));
        $this->assertTrue((bool) $socket->write("HELO [127.0.0.1]\r\n"));
        $this->assertStringContainsString('250 ', $this->wait($socket));
        
        $this->assertTrue((bool) $socket->write("STARTTLS\r\n"));
        $this->assertStringContainsString('220 ', $this->wait($socket));
        $this->assertTrue($socket->enableCrypto('tls'));
        $this->assertTrue((bool) $socket->write("HELO [127.0.0.1]\r\n"));
        $this->assertStringContainsString('250 ', $this->wait($socket));
        $this->assertTrue($socket->disconnect());
    }

    public function testEnableCryptoInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $socket = new Socket();
        $socket->enableCrypto('ssl-10.0');
    }

    /**
     * This is used by the enableCrypto test
     *
     * @param resource $socket
     * @return string
     */
    private function wait($socket) : string
    {
        $response = '';
        while (true) {
            $buffer = $socket->read();
            if ($buffer === null) {
                break;
            }
            $response .= $buffer;
            if (substr($buffer, 3, 1) == ' ' or strlen($buffer) === 3) {
                break;
            }
        }

        return $response;
    }
}
