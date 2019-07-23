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

class CounterCachePostFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
        ' id' => ['type' => 'primaryKey'],
        'title' => [
            'type' => 'string',
            'limit' => 255,
            'null' => false,
        ],
        'body' => 'text',
        'replies_count' => [
            'type' => 'integer',
            'default' => 0,
            'null' => false,
           
        ],
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    public $records = [
        [
            'id' => 1,
            'title' => 'First Post',
            'body' => 'Post body goes here',
            'replies_count' => 0,
            'created' => '2018-12-19 13:29:10',
            'modified' => '2018-12-19 13:30:20',
        ],
        [
            'id' => 2,
            'title' => 'Second Post',
            'body' => 'Post body goes here',
            'replies_count' => 0,
            'created' => '2018-12-19 13:31:30',
            'modified' => '2018-12-19 13:32:40',
        ],
        [
            'id' => 3,
            'title' => 'Third Post',
            'body' => 'Third Post Body',
            'replies_count' => 0,
            'created' => '2018-12-19 13:33:50',
            'modified' => '2018-12-19 13:34:59',
        ],
    ];
}
