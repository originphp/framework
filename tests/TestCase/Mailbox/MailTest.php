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

namespace Origin\Test\Mailbox;

use Origin\Mailbox\Mail;

class MailTest extends \PHPUnit\Framework\TestCase
{
    public function testParsing()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $mail = new Mail($message);
        #debug($mail);
        $this->assertEquals(665972478, crc32(serialize($mail)));

        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        $mail = new Mail($message);
        #debug($mail);
        $this->assertEquals(907516392, crc32(serialize($mail)));

        $message = file_get_contents(__DIR__ . '/messages/html-attachment.eml');
        $mail = new Mail($message);

        $mail->attachments[0]['tmp'] = '/tmp/foo'; // change from random
        $this->assertEquals(746718972, crc32(serialize($mail)));

        /**
         * I want to reach reply-to, sender, return-path and add multiple cc/bcc
         */
        $message = <<< EOF
MIME-Version: 1.0
Date: Tue, 12 Nov 2019 08:39:50 +0100
Message-ID: 123@456.com
Subject: Sample Message
From: Jamiel Sharief <jamiel.sharief@gmail.com>
To: Jamiel Sharief <jamiel.sharief@gmail.com>, <foo@bar.com>
Cc: js@originphp.com, Jamiel Sharief <jamiel@originphp.com>
Bcc: <bar@foo.com>,<fooz@bars.com>
Content-Type: text/plain; charset="UTF-8"
Reply-To: "No Reply" <no-reply@example.com>
Sender: "Jimbo" <jimbo@example.com>
Return-Path: "Bounces" <bounce@example.com>


This is a sample text message.
-- 
Jamiel Sharief

EOF;
        $mail = new Mail($message);
        $this->assertEquals(42897348, crc32(serialize($mail)));
    }

    public function testRecipients()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $mail = new Mail($message);
        $this->assertEquals(2, count($mail->recipients()));
    }

    public function testHeaders()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $mail = new Mail($message);
        $this->assertEquals(1382290002, crc32(json_Encode($mail->headers())));
        $this->assertEquals('Jamiel Sharief <jamiel.sharief@gmail.com>', $mail->headers('to'));
        $this->assertNull($mail->headers('batman'));
    }

    public function testContentType()
    {
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertEquals('text/plain', $mail->contentType());
    }

    public function testIsBounce()
    {
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/550-address-not-found.eml'));
        $this->assertTrue($mail->isBounce());
   

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertFalse($mail->isBounce());
    }

    public function testIsDSN()
    {
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/550-address-not-found.eml'));
        $this->assertTrue($mail->isDeliveryStatusReport());

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertFalse($mail->isDeliveryStatusReport());
    }

    public function testIsAutoResponder()
    {
        print __DIR__ . '/messages/autoresponder.eml';
        debug(file_get_contents(__DIR__ . '/messages/autoresponder.eml'));
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/autoresponder.eml'));
        $this->assertTrue($mail->isAutoResponder());

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertFalse($mail->isAutoResponder());
    }
}
