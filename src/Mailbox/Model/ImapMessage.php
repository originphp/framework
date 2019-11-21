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

    protected function initialize(array $config) : void
    {
        # Setup validation rules
        $this->validate('account', 'notBlank');
        $this->validate('message_id', 'notBlank');
    }

    public function findByAccount(string $account)
    {
        $conditions = ['account' => $account];

        return $this->find('first', ['conditions' => $conditions,'fields' => ['id','message_id']]);
    }
}
