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

namespace Origin\Test\Mailbox\Job;

use Origin\TestSuite\OriginTestCase;
use Origin\Mailbox\Model\InboundEmail;
use Origin\Mailbox\Job\MailboxCleanJob;

class MailboxCleanJobTest extends OriginTestCase
{
    public $fixtures = ['Origin.Mailbox'];

    protected function setUp() : void
    {
        $this->loadModel('InboundEmail', [
            'className' => InboundEmail::class
        ]);
    }

    public function testJob()
    {
        $inboundEmail = $this->InboundEmail->first();

        $result = (new MailboxCleanJob())->dispatchNow($inboundEmail);
        
        $this->assertTrue($result);
        $this->assertEquals(0, $this->InboundEmail->count());
    }
}
