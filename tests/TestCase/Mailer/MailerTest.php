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

namespace Origin\Test\Mailer;

use Origin\Email\Message;
use Origin\Mailer\Mailer;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;

class DemoMailer extends Mailer
{
    use TestTrait;

    protected $layout = false;
    
    protected $defaults = [
        'from' => 'no-reply@example.com',
    ];
    /**
     * Work with mocks
     *
     * @var string
     */
    protected $template = 'demo';

    public function execute(array $params) : void
    {
        $this->first_name = $params['first_name'];

        $this->mail([
            'to' => $params['email'],
            'subject' => 'this is the subject message',
        ]);
    }
}

class MailerTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Queue'];
    public function testDispatch()
    {
        $mailer = new DemoMailer();
        $message = $mailer->dispatch([
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
        ]);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertStringContainsString('To: demo@originphp.com', $message->header());
        $this->assertStringContainsString('How is your day so far?', $message->body());
    }

    public function testDispatchLater()
    {
        $params = [
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
        ];
        $this->assertTrue((new DemoMailer())->dispatchLater($params));
    }

    public function testPreview()
    {
        $mailer = new DemoMailer();
        $message = $mailer->dispatch([
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
        ]);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertStringContainsString('To: demo@originphp.com', $message->header());
        $this->assertStringContainsString('How is your day so far?', $message->body());
    }

    public function testCallbacksDispatch()
    {
        $stub = $this->getMockBuilder(DemoMailer::class)
            ->setMethods(['startup','shutdown'])->getMock();

        // Configure the stub.
        $stub->expects($this->once())->method('startup');

        $stub->expects($this->once())->method('shutdown');
        $stub->folder = 'Demo';

        $stub->dispatch([
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
        ]);
    }

    public function testCallbacksPreview()
    {
        $stub = $this->getMockBuilder(DemoMailer::class)
            ->setMethods(['startup','shutdown'])->getMock();

        // Configure the stub.
        $stub->expects($this->once())->method('startup');

        $stub->expects($this->once())->method('shutdown');
        $stub->folder = 'Demo';

        $stub->preview([
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
        ]);
    }
    
    public function testMail()
    {
        $mailer = new DemoMailer();
        $message = $mailer->dispatch([
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
        ]);
        $options = $mailer->getProperty('options');
        $expected = ['to' => 'demo@originphp.com',
            'subject' => 'this is the subject message',
            'from' => 'no-reply@example.com',
            'bcc' => null,
            'cc' => null,
            'sender' => null,
            'replyTo' => null,
            'headers' => [],
            'attachments' => [],
            'format' => 'both',
            'account' => 'test',
            'template' => 'demo',
            'viewVars' => ['first_name' => 'jim'],
            'layout' => false,
            'body' => null,
            'contentType' => 'text'
        ];
        $this->assertEquals($expected, $options);
    }

    public function testSet()
    {
        $mailer = new DemoMailer();
        $mailer->set('key', 'value');
        $mailer->set(['foo' => 'bar']);

        $vars = $mailer->getProperty('viewVars');
        $this->assertEquals(['key' => 'value','foo' => 'bar'], $vars);
    }

    public function testSetGetAttachments()
    {
        $mailer = new DemoMailer();
        $mailer->attachment('/var/www/README.md');
        $mailer->attachment('/var/www/phpunit.xml', 'phpunit.xml.dist');
        $expected = [
            '/var/www/README.md' => 'README.md',
            '/var/www/phpunit.xml' => 'phpunit.xml.dist',
        ];
        $this->assertEquals($expected, $mailer->attachments());
        $mailer = new DemoMailer();
        $mailer->attachments($expected);
        $this->assertEquals($expected, $mailer->attachments());
    }

    public function testSetGetHeaders()
    {
        $mailer = new DemoMailer();
        $mailer->header('Reply-To', 'replies@example.com');
        $mailer->header('Sender', 'sender@example.com');
        $expected = [
            'Reply-To' => 'replies@example.com',
            'Sender' => 'sender@example.com',
        ];
        $this->assertEquals($expected, $mailer->headers());
        $mailer = new DemoMailer();
        $mailer->headers($expected);
        $this->assertEquals($expected, $mailer->headers());
    }
}
