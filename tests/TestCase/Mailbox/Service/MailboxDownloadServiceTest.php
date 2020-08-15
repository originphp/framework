<?php
namespace Origin\Test\Mailbox\Service;

use Origin\TestSuite\OriginTestCase;
use Origin\Mailbox\Model\ImapMessage;
use Origin\Mailbox\Model\InboundEmail;
use Origin\Mailbox\Service\MailboxDownloadService;

class MockMailboxDownloadService extends MailboxDownloadService
{
    protected $messages = [];

    public function setEmails(array $messages)
    {
        $this->messages = $messages;
    }
    protected function download(array $options)
    {
        return $this->messages;
    }
}

class MailboxDownloadServiceTest extends OriginTestCase
{
    public $fixtures = ['Origin.Mailbox','Origin.Queue','Origin.Imap'];

    protected function initialize(): void
    {
        $this->InboundEmail = $this->loadModel('InboundEmail', [
            'className' => InboundEmail::class
        ]);

        $this->Imap = $this->loadModel('Imap', [
            'className' => ImapMessage::class
        ]);
    }

    public function testStubbedProcess()
    {
        $message = <<< EOF
MIME-Version: 1.0
Date: Tue, 21 Nov 2019 16:30:50 +0100
Message-ID: <12345@mail.gmail.com>
Subject: Whassup?
From: Somebody <somebody@gmail.com>
To: You <you@gmail.com>
Content-Type: text/plain; charset="UTF-8"

Whassup?
EOF;

        $mailboxDownload = new MockMailboxDownloadService($this->InboundEmail, $this->Imap);
        $mailboxDownload->setEmails((array) $message);

        $messageId = '<12345@mail.gmail.com>';
        $result = $mailboxDownload->dispatch('test');
        $this->assertTrue($result->success());
        // Check message id
        $this->assertEquals([$messageId], $result->data());

        # Check saved inbound email
        $lastInbound = $this->InboundEmail->first(['order' => ['id' => 'desc']]);
        $this->assertEquals($messageId, $lastInbound->message_id);

        # Check Imap table updated
        $lastImap = $this->Imap->first(['order' => ['id' => 'desc']]);
        $this->assertEquals($messageId, $lastImap->message_id);

        # Check duplicate message is not saved
        $result = $mailboxDownload->dispatch('test');
        $this->assertEquals([], $result->data());
    }

    /**
    * Executes without errors
    *
    * @return void
    */
    public function testExecute()
    {
        $result = (new MailboxDownloadService($this->InboundEmail, $this->Imap))->dispatch('test');
        $this->assertTrue($result->success());
    }
}
