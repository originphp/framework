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

class ProfileFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
         'id' => ['type' => 'integer', 'key' => 'primary','autoIncrement'=>true],
         'user_id' => ['type' => 'integer'],
         'name' => ['type' => 'string'],
         'created' => 'datetime',
         'modified' => 'datetime',
    ];

    public $records = [
        [
            'id'=>1000,
            'user_id' => 1002,
            'description' => 'Profile #1',
            'created'=>'2019-03-27 13:10:00',
            'modified'=>'2019-03-27 13:12:00'
        ],
        [
            'id'=>1001,
            'article_id' => 1000,
            'description' => 'Profile #2',
            'created'=>'2019-03-27 13:11:00',
            'modified'=>'2019-03-27 13:11:00'
        ],
        [
            'id'=>1002,
            'article_id' => 1000,
            'description' => 'Profile #3',
            'created'=>'2019-03-27 13:12:00',
            'modified'=>'2019-03-27 13:10:00'
        ],
        
    ];
}