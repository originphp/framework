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

namespace Origin\Test\Mailer;

use Origin\Mailer\EmailBuilder;
use Origin\TestSuite\TestTrait;
use Origin\Exception\NotFoundException;

class MockEmailBuilder extends EmailBuilder
{
    use TestTrait;
}

class EmailBuilderTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     * To/from/sender/reply-to share the same loop so test em all using
     * the different email types
     *
     * @return void
     */
    public function testBuildToFromSenderReplyToAndSubject()
    {
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'sender' => ['noreply@example.com' => 'web application'],
            'replyTo' => 'blackhole@example.com',
            'subject' => 'test build 1',
            'folder' => 'Demo',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'both',
            'layout' => 'email',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        $this->assertContains('To: js@example.com', $message->header());
        $this->assertContains('From: sam@example.com', $message->header());
        $this->assertContains('Sender: web application <noreply@example.com', $message->header());
        $this->assertContains('Reply-To: blackhole@example.com', $message->header());
        $this->assertContains('Subject: test build 1', $message->header());
    }

    /**
     * Test together
     *
     * @return void
     */
    public function testBuildCc()
    {
        $options = [
            'to' => ['js@example.com'],
            'from' => ['sam@example.com' => 'sam divine'],
            'sender' => 'noreply@example.com',
            'replyTo' => 'blackhole@example.com',
            'subject' => 'test build 1',
            'folder' => 'Demo',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'both',
            'layout' => 'email',
            'cc' => 'cc1@example.com',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        $this->assertContains('Cc: cc1@example.com', $message->header());

        $options['cc'] = ['cc2@example.com','cc3@example.com' => 'Mr CC3'];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        $this->assertContains('Cc: cc2@example.com, Mr CC3 <cc3@example.com>', $message->header());
    }

    public function testBuildBcc()
    {
        $options = [
            'to' => ['js@example.com'],
            'from' => ['sam@example.com' => 'sam divine'],
            'sender' => 'noreply@example.com',
            'replyTo' => 'blackhole@example.com',
            'subject' => 'test build 1',
            'folder' => 'Demo',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'both',
            'layout' => 'email',
            'bcc' => 'bcc1@example.com',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        $this->assertContains('Bcc: bcc1@example.com', $message->header());

        $options['bcc'] = ['bcc2@example.com','bcc3@example.com' => 'Mr bcc3'];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        $this->assertContains('Bcc: bcc2@example.com, Mr bcc3 <bcc3@example.com>', $message->header());
    }

    public function testBuildHeaders()
    {
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'subject' => 'test build 2',
            'folder' => 'Demo',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'both',
            'layout' => 'email',
            'headers' => [
                'Reply-To' => 'noreply@example.com',
            ],
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        $this->assertContains('Reply-To: noreply@example.com', $message->header());
    }

    public function testBuildAttachment()
    {
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'subject' => 'test build 2',
            'folder' => 'Demo',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'both',
            'layout' => 'email',
            'attachments' => [
                ROOT . DS . 'tests' . DS  . 'README.md' => 'Important.md',
                ROOT . DS . 'README.md',
            ],
        ];
      
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        $this->assertContains('Content-Type: text/plain; name="Important.md"', $message->body());
        $this->assertContains('Content-Type: text/plain; name="README.md"', $message->body());
    }

    public function testRenderHtml()
    {
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'sender' => ['noreply@example.com' => 'web application'],
            'replyTo' => 'blackhole@example.com',
            'subject' => 'test build 1',
            'folder' => 'Demo',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'html',
            'layout' => 'email',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
        // Check layout was rendered and values set
        $this->assertContains("<meta content='text/html; charset=UTF-8' http-equiv='Content-Type' />", $message->body());
        $this->assertContains('<p>Hi jon</p>', $message->body());
    }

    public function testRenderText()
    {
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'sender' => ['noreply@example.com' => 'web application'],
            'replyTo' => 'blackhole@example.com',
            'subject' => 'test build 1',
            'folder' => 'Demo',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'text',
            'layout' => 'email',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();

        $this->assertContains('Content-Type: text/plain; charset="UTF-8"', $message->header());
        $this->assertContains("Hi jon,\r\n", $message->body());
    }

    public function testRenderHtmlException()
    {
        $this->expectException(NotFoundException::class);
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'subject' => 'test build 1',
            'folder' => 'DoesNotExist',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'html',
            'layout' => 'email',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
    }

    public function testRenderTextException()
    {
        $this->expectException(NotFoundException::class);
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'subject' => 'test build 1',
            'folder' => 'DoesNotExist',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'text',
            'layout' => 'email',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
    }

    public function testRenderTextConvert()
    {
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'sender' => ['noreply@example.com' => 'web application'],
            'replyTo' => 'blackhole@example.com',
            'subject' => 'test build 1',
            'folder' => 'Welcome',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'both',
            'layout' => 'email',
        ];
        $builder = new EmailBuilder($options);
        $message = $builder->build(true)->send();
      
        $this->assertContains("Welcome jon\r\n", $message->body());
        $this->assertContains('<h1>Welcome jon</h1>', $message->body());
    }

    public function testPluginpaths()
    {
        $options = [
            'to' => 'js@example.com',
            'from' => ['sam@example.com'],
            'subject' => 'test build 1',
            'folder' => 'Welcome',
            'viewVars' => ['first_name' => 'jon'],
            'format' => 'both',
            'layout' => 'email',
        ];
        $builder = new MockEmailBuilder($options);
        $expected = PLUGINS .DS . 'my_plugin' . DS . 'src' . DS . 'View' . DS . 'Mailer' .DS . 'SendUserNotification' ;
        $this->assertEquals($expected, $builder->callMethod('getPath', ['MyPlugin.SendUserNotification']));

        $expected = PLUGINS .DS . 'my_plugin' . DS . 'src' . DS . 'View' . DS . 'Layout' .DS . 'mailer.ctp' ;
        $this->assertEquals($expected, $builder->callMethod('getLayoutFilename', ['MyPlugin.mailer']));
    }
}
