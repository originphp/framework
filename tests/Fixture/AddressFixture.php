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

class AddressFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
         'id' => ['type' => 'primaryKey'],
         'author_id' => ['type' => 'integer'],
         'description' => 'text',
         'created' => 'datetime',
         'modified' => 'datetime',
    ];

    public $records = [
        [
            'id'=>1000,
            'author_id' => 1003,
            'description' => 'Address #1',
            'created'=>'2019-03-27 13:10:00',
            'modified'=>'2019-03-27 13:12:00'
        ],
        [
            'id'=>1001,
            'author_id' => 1002,
            'description' => 'Address #2',
            'created'=>'2019-03-27 13:11:00',
            'modified'=>'2019-03-27 13:11:00'
        ],
        [
            'id'=>1002,
            'author_id' => 1000,
            'description' => 'Address #3',
            'created'=>'2019-03-27 13:12:00',
            'modified'=>'2019-03-27 13:10:00'
        ]
    ];
}
