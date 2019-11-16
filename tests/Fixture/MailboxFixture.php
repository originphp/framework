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

namespace Origin\Test\Fixture;

use Origin\TestSuite\Fixture;

class MailboxFixture extends Fixture
{
    protected $table = 'mailbox';
    
    protected $schema = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
            'message_id' => ['type' => 'string', 'null' => false, 'default' => null],
            'checksum' => ['type' => 'string', 'limit' => 40, 'null' => false, 'default' => null],
            'message' => ['type' => 'text', 'limit' => 4294967295, 'null' => false, 'default' => null],
            'status' => ['type' => 'string', 'null' => false, 'default' => null],
            'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
            'modified' => ['type' => 'datetime', 'null' => false, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => 'id'],
        ],
        'options' => ['engine' => 'InnoDB', 'autoIncrement' => 1000]
    ];

    protected $records = [
        [
            'message_id' => '<CAD05h8p3WCJLqVLVLebaE03KskpD8+AGEHEjZJ1JvnJpuh2+1w@mail.gmail.com>',
            'checksum' => 'f630f6d31746092ec6dfe627d973d224c9f0dcb8',
            'message' => 'MIME-Version: 1.0
Date: Tue, 12 Nov 2019 08:39:50 +0100
Message-ID: <CAD05h8p3WCJLqVLVLebaE03KskpD8+AGEHEjZJ1JvnJpuh2+1w@mail.gmail.com>
Subject: Re: Ticket 5000
From: Some User <demo@example.com>
To: Support <support@company.com>
Content-Type: text/plain; charset="UTF-8"

Help its not working',
            'status' => 'pending',
            'created' => '2019-11-11 16:11:00',
            'modified' => '2019-11-11 16:11:00',
        ]
    ];
}
