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

use Origin\Model\Model;
use Origin\Model\Concern\Timestampable;

class ImapMessage extends Model
{
    use Timestampable;
    protected $table = 'imap';

    public function findByAccount(string $account)
    {
        $conditions = ['account' => $account];

        return $this->find('first', ['conditions' => $conditions,'fields' => ['id','message_id']]);
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
