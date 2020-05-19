<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace Origin\Mailbox\Job;

use Origin\Job\Job;
use Origin\Mailbox\Mail;
use Origin\Model\Entity;
use Origin\Mailbox\Mailbox;
use Origin\Mailbox\Model\InboundEmail;

class MailboxJob extends Job
{
    protected $queue = 'default';
    protected $wait = null;
    protected $timeout = 60;

    /**
     * Inbound Email Model
     *
     * @var \Origin\Mailbox\InboundEmail
     */
    protected $InboundEmail;

    protected function initialize(): void
    {
        $this->loadModel('InboundEmail', ['className' => InboundEmail::class]);
    }

    protected function execute(Entity $inboundEmail): void
    {
        $mail = new Mail($inboundEmail->message);
        $mailbox = Mailbox::mailbox($mail->recipients());

        if ($mailbox) {
            (new $mailbox($inboundEmail))->dispatch();
        }
    }
}
