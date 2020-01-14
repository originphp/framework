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

namespace Origin\Test\Fixture;

use Origin\TestSuite\Fixture;

/**
 * Used for IMAP mailbox sycing
 */
class ImapFixture extends Fixture
{
    protected $table = 'imap';
    
    protected $schema = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
            'account' => ['type' => 'string', 'null' => false, 'default' => null],
            'message_id' => ['type' => 'string', 'null' => false, 'default' => null],
            'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
            'modified' => ['type' => 'datetime', 'null' => false, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => 'id'],
        ],
        'indexes' => [
            'imap_account_idx' => ['type' => 'index', 'column' => 'account'],
        ],
        'options' => ['engine' => 'InnoDB', 'autoIncrement' => 1000],
    ];

    protected $records = [
        [
            'account' => 'tesxt',
            'message_id' => '<CAD05h8p3WCJLqVLVLebaE03KskpD8+AGEHEjZJ1JvnJpuh2+1w@mail.gmail.com>',
            'created' => '2019-11-11 16:11:00',
            'modified' => '2019-11-11 16:11:00',
        ]
    ];
}
