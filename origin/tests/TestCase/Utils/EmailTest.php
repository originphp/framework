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

namespace Origin\Test\Utils;

use Origin\Utils\Email;
use Origin\TestSuite\TestTrait;
use Origin\Exception\Exception;

class MockEmail extends Email
{
    use TestTrait;
    protected $boundary = '0000000000000000000000000000';
    public static $backup = [];
    
    public static function backup()
    {
        static::$backup = static::$config;
    }
    /**
     * Reset config for testing
     */
    public static function reset()
    {
        static::$config = static::$backup;
    }
}

class EmailTest extends \PHPUnit\Framework\TestCase
{
    public function testTo()
    {
        $Email = new MockEmail();
        $Email = $Email->to('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $property = $Email->getProperty('to');
        $this->assertEquals(['james@originphp.com',null], $property[0]);

        $Email = $Email->to('james@originphp.com', 'James');
        $property = $Email->getProperty('to');
        $this->assertEquals(['james@originphp.com','James'], $property[0]);
    }
    public function testFrom()
    {
        $Email = new MockEmail();
        $Email = $Email->from('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $property = $Email->getProperty('from');

        $this->assertEquals(['james@originphp.com',null], $property);

        $Email = $Email->from('james@originphp.com', 'James');
        $property = $Email->getProperty('from');
        $this->assertEquals(['james@originphp.com','James'], $property);
    }

    public function testSender()
    {
        $Email = new MockEmail();
        $Email = $Email->sender('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $property = $Email->getProperty('sender');
        $this->assertEquals(['james@originphp.com',null], $property);

        $Email = $Email->sender('james@originphp.com', 'James');
        $property = $Email->getProperty('sender');
        $this->assertEquals(['james@originphp.com','James'], $property);
    }

    public function testReplyTo()
    {
        $Email = new MockEmail();
        $Email = $Email->replyTo('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $property = $Email->getProperty('replyTo');
        $this->assertEquals(['james@originphp.com',null], $property);

        $Email = $Email->replyTo('james@originphp.com', 'James');
        $property = $Email->getProperty('replyTo');
        $this->assertEquals(['james@originphp.com','James'], $property);
    }

    public function testReturnPath()
    {
        $Email = new MockEmail();
        $Email = $Email->returnPath('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $property = $Email->getProperty('returnPath');
        $this->assertEquals(['james@originphp.com',null], $property);

        $Email = $Email->returnPath('james@originphp.com', 'James');
        $property = $Email->getProperty('returnPath');
        $this->assertEquals(['james@originphp.com','James'], $property);
    }

    public function testBcc()
    {
        $Email = new MockEmail();
        $Email = $Email->bcc('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $property = $Email->getProperty('bcc');
        $this->assertEquals(['james@originphp.com',null], $property[0]);

        $Email = $Email->bcc('james@originphp.com', 'James');
        $property = $Email->getProperty('bcc');
        $this->assertEquals(['james@originphp.com','James'], $property[0]);
    }

    public function testCc()
    {
        $Email = new MockEmail();
        $Email = $Email->cc('james@originphp.com');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $property = $Email->getProperty('cc');
        $this->assertEquals(['james@originphp.com',null], $property[0]);

        $Email = $Email->cc('james@originphp.com', 'James');
        $property = $Email->getProperty('cc');
        $this->assertEquals(['james@originphp.com','James'], $property[0]);
    }
    /**
     * @depends testCc
     */
    public function testAddCc()
    {
        $Email = new MockEmail();
        $Email = $Email->cc('james@originphp.com', 'James');
        $Email = $Email->addCc('guest@originphp.com', 'Guest');
        $property = $Email->getProperty('cc');
        $this->assertEquals(['guest@originphp.com','Guest'], $property[1]);
    }

    /**
     * @depends testBcc
     */
    public function testAddBcc()
    {
        $Email = new MockEmail();
        $Email = $Email->bcc('james@originphp.com', 'James');
        $Email = $Email->addBcc('guest@originphp.com', 'Guest');
        $property = $Email->getProperty('bcc');
        $this->assertEquals(['guest@originphp.com','Guest'], $property[1]);
    }

    public function testSubject()
    {
        $Email = new MockEmail();
        $Email = $Email->subject('A subject line');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $this->assertEquals('A subject line', $Email->getProperty('subject'));
    }

    public function testTextMessage()
    {
        $Email = new MockEmail();
        $Email = $Email->textMessage('Text message.');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $this->assertEquals('Text message.', $Email->getProperty('textMessage'));
    }

    public function testHtmlMessage()
    {
        $Email = new MockEmail();
        $Email = $Email->htmlMessage('<p>Html message.</p>');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $this->assertEquals('<p>Html message.</p>', $Email->getProperty('htmlMessage'));
    }

    public function testAddHeader()
    {
        $Email = new MockEmail();
        $Email = $Email->addHeader('X-mailer', 'OriginPHP');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);
        $this->assertEquals(['X-mailer'=>'OriginPHP'], $Email->getProperty('additionalHeaders'));
    }

    public function testAddAttachment()
    {
        $Email = new MockEmail();
        $Email = $Email->addAttachment(ROOT . DS . 'webroot' . DS  .'css'  . DS. 'default.css');
        $this->assertInstanceOf('Origin\Utils\Email', $Email);

        $Email->addAttachment(ROOT . DS . 'webroot' . DS  .'css' . DS. 'debug.css', 'Debugger.css');
        
        $expected = [
            ROOT . DS . 'webroot' . DS  .'css'  . DS. 'default.css' => 'default.css',
            ROOT . DS . 'webroot' . DS  .'css' . DS. 'debug.css' => 'Debugger.css'
        ];
        $this->assertSame($expected, $Email->getProperty('attachments'));
    }

    /**
     * @depends testAddAttachment
     */
    public function testAddAttachments()
    {
        $Email = new MockEmail();
        $Email = $Email->addAttachments([
            ROOT . DS . 'webroot' . DS  .'css'  . DS. 'default.css',
            ROOT . DS . 'webroot' . DS  .'css' . DS. 'debug.css' => 'Debugger.css'
        ]);
        $this->assertInstanceOf('Origin\Utils\Email', $Email);

        $expected = [
            ROOT . DS . 'webroot' . DS  .'css'  . DS. 'default.css' => 'default.css',
            ROOT . DS . 'webroot' . DS  .'css' . DS. 'debug.css' => 'Debugger.css'
        ];
        
        $this->assertSame($expected, $Email->getProperty('attachments'));
    }

    public function testBuildMessageHeaderCore()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('test #1')
              ->textMessage('this is a test');
        
        $headers = $Email->callMethod('buildHeaders');
    
        $this->assertEquals('1.0', $headers['MIME-Version']);
        $this->assertEquals(date('r'), $headers['Date']);
        $validUUID = (bool) preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}@originphp.com$/i', $headers['Message-ID']);
        $this->assertTrue($validUUID);
        $this->assertEquals('test #1', $headers['Subject']);
        $this->assertEquals('mailer@originphp.com', $headers['From']);
        $this->assertEquals('james@originphp.com', $headers['To']);
        $this->assertEquals('text/plain; charset="UTF-8"', $headers['Content-Type']);

        // Adjust data
        $Email->to('james@originphp.com', 'James')
              ->from('mailer@originphp.com', 'OriginPHP Mailer');
        $headers = $Email->callMethod('buildHeaders');

        $this->assertEquals('OriginPHP Mailer <mailer@originphp.com>', $headers['From']);
        $this->assertEquals('James <james@originphp.com>', $headers['To']);
    }

    public function testBuildMessageHeaderOptional()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('test #2')
              ->textMessage('this is a test');

        $Email->addHeader('X-mailer', 'OriginPHP');
        
        $Email->bcc('guest1@originphp.com');
        $Email->addBcc('guest2@originphp.com', 'Guest 2');

        $Email->cc('guest3@originphp.com');
        $Email->addCc('guest4@originphp.com', 'Guest 4');

        
        $headers = $Email->callMethod('buildHeaders');
        $this->assertEquals('OriginPHP', $headers['X-mailer']);
        $this->assertEquals('guest1@originphp.com, Guest 2 <guest2@originphp.com>', $headers['Bcc']);
        $this->assertEquals('guest3@originphp.com, Guest 4 <guest4@originphp.com>', $headers['Cc']);
    }

    public function testBuildMessageHeaderEncoding()
    {
        // Check subject and names of headers are encoded
        $Email = new MockEmail();
        $Email->to('ragnar@originphp.com', 'Ragnarr Loþbrók')
              ->from('mailer@originphp.com')
              ->subject('Valhöll')
              ->textMessage('this is a test');
        $headers = $Email->callMethod('buildHeaders');
       
        $this->assertEquals('=?UTF-8?B?VmFsaMO2bGw=?=', $headers['Subject']);
        $this->assertEquals('Ragnarr =?UTF-8?B?TG/DvmJyw7Nr?= <ragnar@originphp.com>', $headers['To']);
        $this->assertFalse(isset($headers['Content-Transfer-Encoding'])); // Dont encode message

        // If we have UTF8 chars in message we need the header ContentTransferEncoding
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('test #2')
              ->textMessage('Are you in Valhöll?');

        $headers = $Email->callMethod('buildHeaders');
        $this->assertEquals('quoted-printable', $headers['Content-Transfer-Encoding']);
    }

    public function testBuildMessageHeaderContentType()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('test #1')
              ->textMessage('this is a test');
        
        $headers = $Email->callMethod('buildHeaders');

        $this->assertEquals('text/plain; charset="UTF-8"', $headers['Content-Type']);

        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('test #1')
              ->htmlMessage('<p>this is a test</p>')
              ->format('html');
        
        $headers = $Email->callMethod('buildHeaders');
      
        $this->assertEquals('text/html; charset="UTF-8"', $headers['Content-Type']);

        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('test #1')
              ->textMessage('this is a test')
              ->htmlMessage('<p>this is a test</p>')
              ->format('both');

        $headers = $Email->callMethod('buildHeaders');

        $boundary = $Email->getProperty('boundary');
        $this->assertEquals("multipart/alternative; boundary=\"{$boundary}\"", $headers['Content-Type']);
    }

    public function testCreateMessageText()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('text test')
              ->textMessage('this is a test');
        $result = $result = $this->messageToString($Email->callMethod('buildMessage'));
        $this->assertEquals("this is a test\r\n", $result);
    }

    public function testCreateMessageTextAttachments()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('text test')
              ->textMessage('this is a test');
        
        $tempfile = tempnam(sys_get_temp_dir(), 'tmp');
        file_put_contents($tempfile, 'foo/bar');

        $Email->addAttachment($tempfile, 'test.txt');
        $headers = $Email->callMethod('buildHeaders');
        $expected = 'multipart/mixed; boundary="0000000000000000000000000000"';
        $this->assertEquals($expected, $headers['Content-Type']);
        $result = $this->messageToString($Email->callMethod('buildMessage'));
  
        $expected = "--0000000000000000000000000000\r\nContent-Type: text/plain; charset=\"UTF-8\"\r\n\r\nthis is a test\r\n\r\n--0000000000000000000000000000\r\nContent-Type: text/plain; name=\"test.txt\"\r\nContent-Disposition: attachment\r\nContent-Transfer-Encoding: base64\r\n\r\nZm9vL2Jhcg==\r\n\r\n\r\n--0000000000000000000000000000--";
        $this->assertEquals($expected, $result);
    }

    public function testCreateMessageHtml()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('html test')
              ->htmlMessage('<p>this is a test</p>')
              ->format('html');
        $result = $this->messageToString($Email->callMethod('buildMessage'));
        $this->assertEquals("<p>this is a test</p>\r\n", $result);
    }

    public function testCreateMessageHtmlAttachments()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('text test')
              ->htmlMessage('<p>this is a test</p>')
              ->format('html');
        
        $tempfile = tempnam(sys_get_temp_dir(), 'tmp');
        file_put_contents($tempfile, 'foo/bar');

        $Email->addAttachment($tempfile, 'test.txt');
        $headers = $Email->callMethod('buildHeaders');
        $expected = 'multipart/mixed; boundary="0000000000000000000000000000"';
        $this->assertEquals($expected, $headers['Content-Type']);
        $result = $this->messageToString($Email->callMethod('buildMessage'));
    
        $expected = "--0000000000000000000000000000\r\nContent-Type: text/html; charset=\"UTF-8\"\r\n\r\n<p>this is a test</p>\r\n\r\n--0000000000000000000000000000\r\nContent-Type: text/plain; name=\"test.txt\"\r\nContent-Disposition: attachment\r\nContent-Transfer-Encoding: base64\r\n\r\nZm9vL2Jhcg==\r\n\r\n\r\n--0000000000000000000000000000--";
        $this->assertEquals($expected, $result);
    }

    public function testCreateMessageBoth()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('text test')
              ->textMessage('this is a test')
              ->htmlMessage('<p>this is a test</p>')
              ->format('both');
        
        $result = $this->messageToString($Email->callMethod('buildMessage'));
       
        $expected = "--0000000000000000000000000000\r\nContent-Type: text/plain; charset=\"UTF-8\"\r\n\r\nthis is a test\r\n\r\n--0000000000000000000000000000\r\nContent-Type: text/html; charset=\"UTF-8\"\r\n\r\n<p>this is a test</p>\r\n\r\n--0000000000000000000000000000--";

        $this->assertEquals($expected, $result);

        // Check Encoding is added when needed
        $Email->to('ragnar@originphp.com')
              ->textMessage('Are you in Valhöll?')
              ->htmlMessage('<p>Are you in Valhöll?</p>');
        $result = $this->messageToString($Email->callMethod('buildMessage'));
        //pr(str_replace("\r\n", '\r\n', $result));
        $expected = "--0000000000000000000000000000\r\nContent-Type: text/plain; charset=\"UTF-8\"\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nAre you in Valh=C3=B6ll?\r\n\r\n--0000000000000000000000000000\r\nContent-Type: text/html; charset=\"UTF-8\"\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n<p>Are you in Valh=C3=B6ll?</p>\r\n\r\n--0000000000000000000000000000--";
     
        $this->assertEquals($expected, $result);
    }


    public function testCreateMessageBothAttachments()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('text test')
              ->textMessage('this is a test')
              ->htmlMessage('<p>this is a test</p>')
              ->format('both');
        
        $tempfile = tempnam(sys_get_temp_dir(), 'tmp');
        file_put_contents($tempfile, 'foo/bar');

        $Email->addAttachment($tempfile, 'test.txt');
        $headers = $Email->callMethod('buildHeaders');
        $expected = 'multipart/mixed; boundary="0000000000000000000000000000"';
        $this->assertEquals($expected, $headers['Content-Type']);
        $result = $this->messageToString($Email->callMethod('buildMessage'));
        
        $expected = "--0000000000000000000000000000\r\nContent-Type: multipart/alternative; boundary=\"alt-0000000000000000000000000000\"\r\n\r\n--alt-0000000000000000000000000000\r\nContent-Type: text/plain; charset=\"UTF-8\"\r\n\r\nthis is a test\r\n\r\n--alt-0000000000000000000000000000\r\nContent-Type: text/html; charset=\"UTF-8\"\r\n\r\n<p>this is a test</p>\r\n\r\n--0000000000000000000000000000\r\nContent-Type: text/plain; name=\"test.txt\"\r\nContent-Disposition: attachment\r\nContent-Transfer-Encoding: base64\r\n\r\nZm9vL2Jhcg==\r\n\r\n\r\n--0000000000000000000000000000--";
        $this->assertEquals($expected, $result);
    }

    /**
     * Check that multiple attachments work properly
     */
    public function testMultipleAttachments()
    {
        $Email = new MockEmail();
        $Email->to('james@originphp.com')
              ->from('mailer@originphp.com')
              ->subject('text test')
              ->textMessage('this is a test');
        
        // Needs a unique filename
        $tempfile = tempnam(sys_get_temp_dir(), 'tmp');
        file_put_contents($tempfile, 'foo/bar');
        $Email->addAttachment($tempfile, 'test1.txt');

        $tempfile = tempnam(sys_get_temp_dir(), 'tmp');
        file_put_contents($tempfile, 'foo/bar');
        $Email->addAttachment($tempfile, 'test2.txt');

        $headers = $Email->callMethod('buildHeaders');
        $expected = 'multipart/mixed; boundary="0000000000000000000000000000"';
        $this->assertEquals($expected, $headers['Content-Type']);
        $result = $this->messageToString($Email->callMethod('buildMessage'));
  
        $expected = "--0000000000000000000000000000\r\nContent-Type: text/plain; charset=\"UTF-8\"\r\n\r\nthis is a test\r\n\r\n--0000000000000000000000000000\r\nContent-Type: text/plain; name=\"test1.txt\"\r\nContent-Disposition: attachment\r\nContent-Transfer-Encoding: base64\r\n\r\nZm9vL2Jhcg==\r\n\r\n\r\n--0000000000000000000000000000\r\nContent-Type: text/plain; name=\"test2.txt\"\r\nContent-Disposition: attachment\r\nContent-Transfer-Encoding: base64\r\n\r\nZm9vL2Jhcg==\r\n\r\n\r\n--0000000000000000000000000000--";
        $this->assertEquals($expected, $result);
    }

    /**
     * Use this whilst creating or debugging tests to see output
     *
     * @param [type] $result
     * @return void
     */
    protected function debugResult($result)
    {
        pr($result);
        pr(str_replace("\r\n", '\r\n', $result));
    }

    /**
     * To help with testing
     *
     * @param [type] $result
     * @return void
     */
    protected function messageToString($result)
    {
        return implode("\r\n", $result);
    }

    public function testConfig()
    {
        MockEmail::backup(); // Backup Original Config

        $config = [  'host' => 'ssl://smtp.gmail.com', 'port' => 465, 'username' => 'test@originphp.com', 'password' => 'secret' ,'tls'=>true,'client'=>null,'timeout'=>30];
        $expected = [  'host' => 'ssl://smtp.gmail.com', 'port' => 465, 'username' => 'test@originphp.com', 'password' => 'secret' ,'tls'=>true];
        MockEmail::config('default', $config);
        $this->assertEquals($config, MockEmail::config('default'));
        $this->assertNull(MockEmail::config('gmail'));
        MockEmail::reset();
    }

    public function testAccount()
    {
        $config = [  'host' => 'smtp.example.com', 'port' => 25, 'username' => 'test@example.com', 'password' => 'secret'];
        $expected = ['host' => 'smtp.example.com', 'port' => 25, 'username' => 'test@example.com', 'password' => 'secret','tls'=>false,'client'=>null,'timeout'=>30];
        $Email = new MockEmail($config);
        $this->assertEquals($expected, $Email->getProperty('account'));
        $this->assertEquals($expected, $Email->account());

        MockEmail::config('gmail', $config);
        $Email = new MockEmail($config);
        $this->assertEquals($expected, $Email->account());
        MockEmail::reset();
    }
    public function testAccountException()
    {
        $this->expectException(Exception::class);
        $Email = new MockEmail();
        $Email->account('nonExistant');
    }
}
