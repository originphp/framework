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

use Exception;
use Origin\Mailbox\Mail;
use Origin\Mailer\Mailer;
use Origin\Service\Result;
use Origin\Mailbox\Mailbox;
use Origin\TestSuite\OriginTestCase;
use Origin\Mailbox\Model\InboundEmail;

class SupportMailbox extends Mailbox
{
    public $beforeCalled = false;
    public $afterCalled = false;
    public $onSuccessCalled = false;
    public $onErrorCallback = null;

    protected $bounceClass;

    public function bounceMe()
    {
        $this->bounceClass = BounceMailer::class;
    }

    public function failMe()
    {
        $this->bounceClass = 'NonExistantClass';
    }

    protected function initialize(): void
    {
        $this->beforeProcess('beforeProcessCallback');
        $this->afterProcess('afterProcessCallback');
        $this->onSuccess('onSuccessCallback');
        $this->onError('onErrorCallback');
    }
    
    protected function process()
    {
        if ($this->bounceClass) {
            $this->bounceWith($this->bounceClass);
        }
    }

    protected function beforeProcessCallback()
    {
        $this->beforeCalled = true;
    }

    protected function afterProcessCallback()
    {
        $this->afterCalled = true;
    }

    protected function onSuccessCallback()
    {
        $this->onSuccessCalled = true;
    }

    protected function onErrorCallback(Exception $exception)
    {
        $this->onErrorCallback = $exception;
    }
}

class BounceMailer extends Mailer
{
    protected function execute()
    {
    }
}

class MailboxTest extends OriginTestCase
{
    public $fixtures = ['Origin.Mailbox','Origin.Queue','Origin.Imap'];
    
    /**
     * @var \Origin\Mailbox\Model\InboundEmail
     */
    protected $InboundEmail;

    protected function initialize(): void
    {
        $this->InboundEmail = $this->loadModel('InboundEmail', [
            'className' => InboundEmail::class
        ]);
    }
    public function testRouting()
    {
        Mailbox::route('/^support@/i', SupportMailbox::class);
        $this->assertEquals(['/^support@/i' => SupportMailbox::class], Mailbox::routes());
        $this->assertNull(Mailbox::routes('foo'));
        $this->assertEquals(SupportMailbox::class, Mailbox::routes('/^support@/i'));
        
        $inboundEmail = $this->InboundEmail->find('first');
        $mail = new Mail($inboundEmail->message);
        $this->assertEquals(SupportMailbox::class, Mailbox::mailbox($mail->recipients()));
       
        $this->assertNull(Mailbox::mailbox(['foo@example.com']));
    }

    public function testConstruct()
    {
        $inboundEmail = $this->InboundEmail->find('first');
        $mailbox = new SupportMailbox($inboundEmail);
        $this->assertInstanceOf(Mail::class, $mailbox->mail());
        $this->assertEquals('support@company.com', $mailbox->mail()->to);
    }

    public function testDispatch()
    {
        $inboundEmail = $this->InboundEmail->find('first');
        $mailbox = new SupportMailbox($inboundEmail);
        $mailbox->dispatch();
        $this->assertTrue($mailbox->beforeCalled);
        $this->assertTrue($mailbox->afterCalled);
        $this->assertTrue($mailbox->onSuccessCalled);
        $inboundEmail = $this->InboundEmail->find('first');
        $this->assertEquals('delivered', $inboundEmail->status);
    }

    public function testDispatchWithBounce()
    {
        $inboundEmail = $this->InboundEmail->find('first');
        $mailbox = new SupportMailbox($inboundEmail);
        $mailbox->bounceMe();
        $mailbox->dispatch();
        $this->assertTrue($mailbox->beforeCalled);
        $this->assertTrue($mailbox->afterCalled);
        $this->assertFalse($mailbox->onSuccessCalled);
        $inboundEmail = $this->InboundEmail->find('first');
        $this->assertEquals('bounced', $inboundEmail->status);
    }

    public function testDispatchFail()
    {
        $inboundEmail = $this->InboundEmail->find('first');
        $mailbox = new SupportMailbox($inboundEmail);
        $mailbox->failMe();
        $mailbox->dispatch();
        $this->assertTrue($mailbox->beforeCalled);
        $this->assertFalse($mailbox->afterCalled);
        $this->assertFalse($mailbox->onSuccessCalled);
        $inboundEmail = $this->InboundEmail->find('first');
        $this->assertEquals('failed', $inboundEmail->status);
        $this->assertInstanceOf(Exception::class, $mailbox->onErrorCallback); // check this passed
    }

    public function testDownload()
    {
        if (! env('EMAIL_IMAP_USERNAME') || ! env('EMAIL_IMAP_PASSWORD')) {
            $this->markTestSkipped(
                'Imap username and password not setup'
            );
        }

        $result = Mailbox::download('test', ['limit' => 1]);
        $this->assertInstanceOf(Result::class, $result);
    }
}
