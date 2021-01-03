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

namespace Origin\Test\Mailbox;

use Generator;
use Origin\Mailbox\MailFetcher;
use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;

class MailFetcherTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidProtocol()
    {
        $this->expectException(InvalidArgumentException::class);
        new MailFetcher(['host' => 'localhost','protocol' => 'sftp']);
    }
    public function testInvalidEncryption()
    {
        $this->expectException(InvalidArgumentException::class);
        new MailFetcher(['host' => 'localhost','encryption' => 'pgp']);
    }

    public function testBadConnection()
    {
        $this->expectException(Exception::class);
        $fetcher = new MailFetcher([
            'host' => 'smtp.gmail.com','port' => 993,'encryption' => 'ssl','validateCert' => false,
            'username' => 'me','password' => 'secret','timeout' => 1
        ]);
        $fetcher->download();
    }

    public function testDownloadImap()
    {
        if (! env('EMAIL_IMAP_USERNAME') or ! env('EMAIL_IMAP_PASSWORD')) {
            $this->markTestSkipped(
                'EMAIL username and password not setup'
            );
        }

        $fetcher = new MailFetcher([
            'host' => env('EMAIL_IMAP_HOST'),'port' => env('EMAIL_IMAP_PORT'),'encryption' => env('EMAIL_IMAP_ENCRYPTION', null),'validateCert' => false,
            'username' => env('EMAIL_IMAP_USERNAME'),'password' => env('EMAIL_IMAP_PASSWORD'),'timeout' => 5
        ]);

        $messages = $fetcher->download(['limit' => 1]);
        $this->assertGreaterThanOrEqual(1, $fetcher->count());

        /**
         * Trigger generator
         */
        foreach ($messages as $message) {
            // do nothing
        }
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
        $this->assertStringContainsString('From: ', $message);
        $this->assertStringContainsString('To: ', $message);
        $this->assertStringContainsString('Subject: ', $message);

        $lines = explode("\n", str_replace("\r\n", "\n", $message));
        $messageId = null;
        foreach ($lines as $line) {
            if (preg_match('/^Message-ID: (.*)/is', $line, $matches)) {
                $messageId = $matches[1];
            }
        }
        unset($fetcher); // Close connection

        return $messageId;
    }

    /**
     * @depends testDownloadImap
     *
     * @return void
     */
    public function testSync($messageId)
    {
        $fetcher = new MailFetcher([
            'host' => env('EMAIL_IMAP_HOST'),'port' => env('EMAIL_IMAP_PORT'),'encryption' => env('EMAIL_IMAP_ENCRYPTION', null),'validateCert' => false,
            'username' => env('EMAIL_IMAP_USERNAME'),'password' => env('EMAIL_IMAP_PASSWORD')
        ]);

        $fetcher->download(['limit' => 1,'messageId' => $messageId]);
        $this->assertEquals(0, $fetcher->count());
        unset($fetcher); // Close connection
    }

    /**
     * Test connection, and if there are no emails skip rest of checks
     *
     * @return void
     */
    public function testDownloadPop3()
    {
        if (! env('EMAIL_POP3_USERNAME') or ! env('EMAIL_POP3_PASSWORD')) {
            $this->markTestSkipped(
                'EMAIL username and password not setup'
            );
        }
        $fetcher = new MailFetcher([
            'host' => env('EMAIL_POP3_HOST'),'port' => env('EMAIL_POP3_PORT'),'encryption' => env('EMAIL_POP3_ENCRYPTION', null),'validateCert' => false,
            'username' => env('EMAIL_POP3_USERNAME'),'password' => env('EMAIL_POP3_PASSWORD'),'protocol' => 'pop3'
        ]);
        // there may or may not be messages if we keep running tests
        $messages = $fetcher->download(['limit' => 1]);
        
        $this->assertInstanceOf(Generator::class, $messages);
        if ($fetcher->count() === 0) {
            unset($fetcher); // Close connection
            $this->markTestSkipped('No Messages');
        }

        $this->assertEquals(1, $fetcher->count());
        // its a generator
        foreach ($messages as $message) {
            // do nothing
        }
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
        $this->assertStringContainsString('From: ', $message);
        $this->assertStringContainsString('To: ', $message);
        $this->assertStringContainsString('Subject: ', $message);

        unset($fetcher); // Close connection
    }
}
