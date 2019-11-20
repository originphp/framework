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
declare(strict_types = 1);
namespace Origin\Mailbox\Model;

use ArrayObject;
use Origin\Core\Config;
use Origin\Model\Model;
use Origin\Mailbox\Mail;
use Origin\Model\Entity;
use Origin\Security\Security;
use Origin\Mailbox\Job\MailboxJob;
use Origin\Mailbox\Job\MailboxCleanJob;
use Origin\Model\Concern\Timestampable;

class InboundEmail extends Model
{
    use Timestampable;
    protected $table = 'mailbox';

    protected function initialize(array $config) : void
    {
        $this->afterCreate('scheduleJobs');
    }

    /**
     * This is a model callback
     *
     * @param \Origin\Model\Entity $inboundEmail
     * @param ArrayObject $options
     * @return void
     */
    protected function scheduleJobs(Entity $inboundEmail, ArrayObject $options) : void
    {
        # Dispatch for Processing
        (new MailboxJob())->dispatch($inboundEmail);

        # Schedule the mailbox cleaner
        $cleanAfter = Config::read('Mailbox.keepEmails') ?? '+30 days';
         
        (new MailboxCleanJob())->schedule($cleanAfter)->dispatch();
    }
    
    public function checksumExists(int $checksum) : bool
    {
        return (bool) $this->where(['checksum' => $checksum])->count();
    }

    /**
     * Creates a new entity from a message
     *
     * @param string $message
     * @return \Origin\Model\Entity
     */
    public function fromMessage(string $message) : Entity
    {
        $mail = new Mail($message);

        return $this->new([
            'message_id' => $mail->messageId,
            'checksum' => Security::hash($message, ['type' => 'sha1']),
            'message' => $message,
            'status' => 'pending'
        ]);
    }

    /**
     * Sets the status of an inbound email
     *
     * @param integer $id
     * @param string $status The following statuses :
     *  - pending: this is newly added
     *  - processing: this is currently being run in a job and processed
     *  - delivered: everything went okay
     *  - bounced: this message was bounced
     *  - failed: an error occured when processing
     * @return boolean
     */
    public function setStatus(int $id, string $status) : bool
    {
        return $this->updateColumn($id, 'status', $status);
    }
}
