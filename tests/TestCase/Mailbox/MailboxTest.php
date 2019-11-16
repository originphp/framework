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
use Origin\Mailbox\Mailbox;
use Origin\Mailbox\Model\InboundEmail;
use Origin\Mailer\Mailer;
use Origin\TestSuite\OriginTestCase;

class SupportMailbox extends Mailbox
{
    public $beforeCalled = false;
    public $afterCalled = false;
    protected $bounceClass;

    public function bounceMe()
    {
        $this->bounceClass = BounceMailer::class;
    }

    public function failMe()
    {
        $this->bounceClass = 'NonExistantClass';
    }

    protected function initialize() : void
    {
        $this->beforeProcess('beforeProcessCallback');
        $this->afterProcess('afterProcessCallback');
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
}

class BounceMailer extends Mailer
{
    protected function execute()
    {
    }
}

class MailboxTest extends OriginTestCase
{
    public $fixtures = ['Origin.Mailbox','Origin.Queue'];
    
    /**
     * @var \Origin\Mailbox\Model\InboundEmail
     */
    protected $InboundEmail;

    protected function initialize() : void
    {
        $this->InboundEmail = $this->loadModel('InboundEmail', ['className'=>InboundEmail::class]);
    }
    public function testRouting()
    {
        Mailbox::route('/^support@/i', SupportMailbox::class);
        $this->assertEquals(['/^support@/i' => SupportMailbox::class], Mailbox::routes());
        $this->assertNull(Mailbox::routes('foo'));
        $this->assertEquals(SupportMailbox::class, Mailbox::routes('/^support@/i'));
        
        $inboundEmail = $this->InboundEmail->find('first');
        $this->assertEquals(SupportMailbox::class, Mailbox::detect($inboundEmail->message));
        $modified = str_replace('support@', 'sales@', $inboundEmail->message);
        $this->assertNull(Mailbox::detect($modified));
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
        $this->assertFalse($mailbox->afterCalled);
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
        $inboundEmail = $this->InboundEmail->find('first');
        $this->assertEquals('failed', $inboundEmail->status);
    }
}
