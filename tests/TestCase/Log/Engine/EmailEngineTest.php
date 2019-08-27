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

namespace Origin\Test\Core;

use Origin\Mailer\Email;
use Origin\Log\Engine\EmailEngine;
use Origin\Exception\InvalidArgumentException;

class MockEmailEngine extends EmailEngine
{
    public function email()
    {
        return $this->lastEmail;
    }
}
class EmailEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultConfig()
    {
        Email::config('demo', ['debug' => true]);
        $engine = new MockEmailEngine(['to' => 'foo@example.com','from' => 'foo@example.com','account' => 'demo']);
        $this->assertEquals('demo', $engine->config('account'));
        $this->assertEquals([], $engine->config('levels'));
        $this->assertEquals([], $engine->config('channels'));
    }
    public function testInvalidToAddress()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new MockEmailEngine(['from' => 'foo@example.com']);
    }
    public function testInvalidToAddressNotNull()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new MockEmailEngine(['to' => 'foo','from' => 'foo@example.com']);
    }
    public function testInvalidEmailAccount()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new MockEmailEngine(['to' => 'foo@example.com','from' => 'foo@example.com','account' => 'foo']);
    }

    public function testAccountFromNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        Email::config('demo', ['debug' => true]);
        $engine = new MockEmailEngine(['to' => 'foo@example.com','account' => 'demo']);
    }

    public function testAccountFromInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        Email::config('demo', ['debug' => true]);
        $engine = new MockEmailEngine(['to' => 'foo@example.com','from' => 'foo','account' => 'demo']);
    }
   
    public function testLog()
    {
        Email::config('demo', ['debug' => true]);
        $engine = new MockEmailEngine([
            'to' => 'you@example.com',
            'from' => 'me@example.com',
            'account' => 'demo',
        ]);
        $id = uniqid();
        $this->assertTrue($engine->log('error', 'Error code {value}', ['value' => $id]));
        $date = date('Y-m-d G:i:s');
        $message = $engine->email()->message();
        $this->assertContains("[{$date}] application ERROR: Error code {$id}", $message);
        $this->assertContains('From: me@example.com', $message);
        $this->assertContains('To: you@example.com', $message);
    }

    public function testLogEmailArray()
    {
        Email::config('demo', ['debug' => true]);
        $engine = new MockEmailEngine([
            'to' => ['you@example.com','jimbo'],
            'from' => ['me@example.com'],
            'account' => 'demo',
        ]);
        $id = uniqid();
     
        $this->assertTrue($engine->log('error', 'Error code {value}', ['value' => $id]));
        $date = date('Y-m-d G:i:s');

        $message = $engine->email()->message();
        $this->assertContains("[{$date}] application ERROR: Error code {$id}", $message);
        $this->assertContains('From: me@example.com', $message);
        $this->assertContains('jimbo <you@example.com>', $message);
    }

    /**
     * This has invalid credentials and will cause the email
     *
     * @return void
     */
    public function testSendFailureException()
    {
        Email::config('your-personal-gmail-account', ['host' => 'smtp.gmail.com','password' => 'your_password']);
        $engine = new MockEmailEngine([
            'to' => 'foo@example.com',
            'from' => 'foo@example.com',
            'account' => 'your-personal-gmail-account', // joke
        ]);
        $this->assertFalse($engine->log('error', 'This will go into a blackhole'));
    }
}
