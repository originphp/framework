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

namespace Origin\Test\Mailbox\Job;

use Origin\Mailbox\Mailbox;
use Origin\Mailbox\Job\MailboxJob;
use Origin\TestSuite\OriginTestCase;
use Origin\Mailbox\Model\InboundEmail;

class SupportMailbox extends Mailbox
{
    protected function process(): void
    {
    }
}

class MailboxJobTest extends OriginTestCase
{
    public $fixtures = ['Origin.Mailbox'];

    protected function setUp(): void
    {
        $this->loadModel('InboundEmail', [
            'className' => InboundEmail::class
        ]);
    }

    public function testJob()
    {
        Mailbox::route('/^support@/i', SupportMailbox::class);
        
        $inboundEmail = $this->InboundEmail->first();

        $result = (new MailboxJob())->dispatchNow($inboundEmail);
        $this->assertTrue($result);

        $inboundEmail = $this->InboundEmail->first();
        $this->assertEquals('delivered', $inboundEmail->status);
    }
}
