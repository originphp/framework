<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
        $this->assertEquals(997208093, crc32(serialize($mail)));

        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        $mail = new Mail($message);

        $this->assertEquals(1191201039, crc32(serialize($mail)));

        $message = file_get_contents(__DIR__ . '/messages/html-attachment.eml');
        $mail = new Mail($message);
  
        $attachments = $mail->attachments();
        $attachments[0]['tmp'] = '/tmp/foo';
        $this->assertEquals('jamiel.to@gmail.com', $mail->to);
        $this->assertEquals('jamiel.from@gmail.com', $mail->from);
        $this->assertEquals('HTML email with Attachment', $mail->subject);
        $this->assertEquals('941afeddf705afd99957c5c8132acfd7', md5($mail->htmlPart));
        $expected = [
            'name' => 'README.md',
            'type' => 'text/plain',
            'size' => '57',
            'tmp' => '/tmp/foo'
        ];
        $attachments = $mail->attachments();
        $attachments[0]['tmp'] = '/tmp/foo';
        $this->assertEquals([$expected], $attachments);

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

        $this->assertEquals(1529504234, crc32(serialize($mail)));
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
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/autoresponder.eml'));
        $this->assertTrue($mail->isAutoResponder());

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertFalse($mail->isAutoResponder());
    }

    public function testIsMultiPart()
    {
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/html.eml'));
        $this->assertTrue($mail->isMultiPart());

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertFalse($mail->isMultiPart());
    }

    public function testHasAttachments()
    {
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text-attachment.eml'));
        $this->assertTrue($mail->hasAttachments());

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertFalse($mail->hasAttachments());
    }

    public function testAttachments()
    {
        $message = file_get_contents(__DIR__ . '/messages/text-attachment.eml');
        $mail = new Mail($message);
        $this->assertTrue($mail->hasAttachments());

        $attachments = $mail->attachments();
        $this->assertIsArray($attachments);
        $this->assertNotEmpty($attachments);

        // no need to test parsing as this in MailParserTest::testAttachments
        $this->assertArrayHasKey('name', $attachments[0]);
        $this->assertArrayHasKey('type', $attachments[0]);
        $this->assertArrayHasKey('size', $attachments[0]);
        $this->assertArrayHasKey('tmp', $attachments[0]);
    }

    public function testHasHtml()
    {
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/html.eml'));
        $this->assertTrue($mail->hasHtml());

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertFalse($mail->hasHtml());
    }

    public function testHasText()
    {
        $mail = new Mail(file_get_contents(__DIR__ . '/messages/text.eml'));
        $this->assertTrue($mail->hasText());

        $mail = new Mail(file_get_contents(__DIR__ . '/messages/html-only.eml'));
        $this->assertFalse($mail->hasText());
    }

    public function testMessage()
    {
        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        $mail = new Mail($message);
 
        $this->assertEquals($mail->header ."\r\n\r\n" . $mail->body, $mail->message());
    }

    public function testToString()
    {
        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        $mail = new Mail($message);
 
        $this->assertEquals($mail->header ."\r\n\r\n" . $mail->body, (string) $mail);
    }
}
