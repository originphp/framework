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

use Origin\Core\Exception\InvalidArgumentException;
use Origin\Mailbox\MailParser;

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
    public function testRawHeader()
    {
        $message = file_get_contents(__DIR__ . '/messages/text.eml');
        $parser = new MailParser($message);
        $this->assertEquals(2803017535, crc32($parser->rawHeader()));
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
        $this->assertEquals(4257203596, crc32($body));
        $this->assertEquals($body, $parser->body('text'));
    }
    public function testBodyHtmlAndTextMessage()
    {
        $message = file_get_contents(__DIR__ . '/messages/html.eml');
        
        $parser = new MailParser($message);
        $body = $parser->body();
        $this->assertEquals(42397819, crc32($body));
        $this->assertEquals($body, $parser->body('html'));
        $this->assertEquals(3882162683, crc32($parser->body('text')));
    }

    public function testBodyDeliveryFailure()
    {
        $message = file_get_contents(__DIR__ . '/messages/550-address-not-found.eml');
        
        $parser = new MailParser($message);
        $body = $parser->body();
        
        $this->assertEquals(4002126370, crc32($body));
        $this->assertEquals($body, $parser->body('html')); #  Check default version is html from gmail
        $this->assertEquals(2221129668, crc32($parser->body('text')));
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
        $this->assertEquals(77, $attachments[0]['size']);
        $this->assertRegExp('/\/tmp\/([a-z0-9]+)$/i', $attachments[0]['tmp']);
    }
}
