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

use Origin\Mailbox\MailParser;
use Origin\Core\Exception\InvalidArgumentException;

class MailParserTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidEmail()
    {
        $this->expectException(InvalidArgumentException::class);
        new MailParser('foo');
    }
    public function testHeaders()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $parser = new MailParser($message);
        $headerArray = var_export($parser->headers(), true);
        $this->assertEquals(2070359253, crc32($headerArray));

        $this->assertEquals('<CAD05h8p3WCJLqVLVLebaE03KskpD8+AGEHEjZJ1JvnJpuh2+1w@mail.gmail.com>', $parser->headers('message-id'));
        $this->assertNull($parser->headers('foo'));
    }
    public function testHeader()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $this->assertEquals(2384335015, crc32((new MailParser($message))->header()));
    }

    public function testMessage()
    {
        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        $parser = new MailParser($message);

        $this->assertEquals(47090491, crc32((new MailParser($message))->message()));
    }

    public function testParseAddresses()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $parser = new MailParser($message);
        $string = 'somebody@originphp.com, Jamiel Sharief <jamiel@originphp.com>';
        $addresses = $parser->parseAddresses($string);
        $expected = [
            ['name' => null,'email' => 'somebody@originphp.com'],
            ['name' => 'Jamiel Sharief','email' => 'jamiel@originphp.com'],
        ];
        $this->assertSame($expected, $addresses);
    }
    public function testBodyTextMessage()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        
        $parser = new MailParser($message);
        $body = $parser->body();
      
        $this->assertEquals(4246420406, crc32($body));
        $this->assertEquals(4257203596, crc32($parser->decoded()));
        $this->assertEquals(4257203596, crc32($parser->textPart()));
    }
    public function testMultiPart()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $parser = new MailParser($message);
        $this->assertFalse($parser->multipart());

        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        $parser = new MailParser($message);
        $this->assertTrue($parser->multipart());
    }

    public function testBodyHtmlAndTextMessage()
    {
        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        $parser = new MailParser($message);
        $body = $parser->body();
      
        $this->assertEquals(1064497483, crc32($body));
        $this->assertEquals(42397819, crc32($parser->decoded()));
        $this->assertEquals(42397819, crc32($parser->htmlPart()));
        $this->assertEquals(3882162683, crc32($parser->textPart()));
    }

    public function testBodyDeliveryFailure()
    {
        $message = file_get_contents(__DIR__ . '/messages/550-address-not-found.eml');
        
        $parser = new MailParser($message);
        $body = $parser->body();
 
        $this->assertEquals(2932135163, crc32($body));
        $this->assertEquals(4002126370, crc32($parser->decoded()));
        $this->assertEquals(4002126370, crc32($parser->htmlPart()));
        $this->assertEquals(2221129668, crc32($parser->textPart()));
    }

    public function testHasAttachments()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $this->assertFalse((new MailParser($message))->hasAttachments());
        
        $message = file_get_contents(__DIR__ . '/messages/html-attachment.eml');
        $this->assertTrue((new MailParser($message))->hasAttachments());
    }

    public function testAttachments()
    {
        $message = file_get_contents(__DIR__ . '/messages/html-attachment.eml');
        
        $parser = new MailParser($message);
        
        $this->assertTrue($parser->hasAttachments());
        $attachments = $parser->attachments();
        $this->assertNotEmpty($attachments);
        $this->assertEquals('README.md', $attachments[0]['name']);
        $this->assertEquals('text/plain', $attachments[0]['type']);
        $this->assertEquals(57, $attachments[0]['size']);
        $this->assertRegExp('/\/tmp\/([a-z0-9]+)$/i', $attachments[0]['tmp']);
    }

    public function testAttachmentsGetBody()
    {
        $message = file_get_contents(__DIR__ . '/messages/html-attachment.eml');
        $parser = new MailParser($message);
        
        $this->assertTrue($parser->hasAttachments());
        $this->assertTrue($parser->hasAttachments());
        $this->assertNotEmpty($parser->attachments());
  
        $this->assertEquals(2150665751, crc32($parser->body()));

        $message = file_get_contents(__DIR__ . '/messages/text-attachment.eml');
        $parser = new MailParser($message);
        
        $this->assertTrue($parser->hasAttachments());
        $this->assertNotEmpty($parser->attachments());
        $this->assertEquals(2130664793, crc32($parser->body()));
    }

    public function testAutoResponder()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $parser = new MailParser($message);
        $this->assertFalse($parser->autoresponder());
        
        $message = file_get_contents(__DIR__ . '/messages/autoresponder.eml');
        $parser = new MailParser($message);
        $this->assertTrue($parser->autoresponder());

        // modify header to other heuristic
        $modified = str_replace('Auto-Submitted: auto-replied', 'X-Auto-Response-Suppress: ALL', $message);
        $parser = new MailParser($modified);
        $this->assertTrue($parser->autoresponder());
        
        // modify header to other heuristic
        $modified = str_replace('Auto-Submitted: auto-replied', 'Precedence: auto-reply', $message);
        $parser = new MailParser($modified);
        $this->assertTrue($parser->autoresponder());
    }

    public function testBounced()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $parser = new MailParser($message);
        $this->assertFalse($parser->bounced());
        
        $message = file_get_contents(__DIR__ . '/messages/550-address-not-found.eml');
        $parser = new MailParser($message);
        $this->assertTrue($parser->bounced());

        // modify header to other heuristic
        $modified = str_replace('X-Failed-Recipients: nobody@originphp.com', 'Subject: Delivery Notification: Delivery has failed', $message);
        $parser = new MailParser($modified);
        $this->assertTrue($parser->bounced());

        // Check Email Error e.g 500 1.1.1
        $modified = str_replace('X-Failed-Recipients: nobody@originphp.com', 'Foo: bar', $message);
        $modified = str_replace('550 5.1.1', 'Action: failed', $modified);
        $parser = new MailParser($modified);
        $this->assertTrue($parser->bounced());

        // Check Email Error e.g 500 1.1.1
        $modified = str_replace('X-Failed-Recipients: nobody@originphp.com', 'Foo: bar', $message);
        $parser = new MailParser($modified);
        $this->assertTrue($parser->bounced());
    }
}
