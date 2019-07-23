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

class AuthorFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
        'id' => ['type' => 'primaryKey'],
        'name' => ['type' => 'string','limit' => 255, 'null' => false],
        'description' => 'text',
        'location' => ['type' => 'string','limit' => 20],
        'rating' => ['type' => 'integer','limit' => 5],
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    public $records = [
        [
            'id' => 1000,
            'name' => 'Author #1',
            'description' => 'Description about Author #1',
            'location' => 'London',
            'rating' => 5,
            'created' => '2019-03-27 13:10:00',
            'modified' => '2019-03-27 13:12:00',
        ],
        [
            'id' => 1001,
            'name' => 'Author #2',
            'description' => 'Description about Author #2',
            'location' => 'New York',
            'rating' => 3,
            'created' => '2019-03-27 13:11:00',
            'modified' => '2019-03-27 13:11:00',
        ],
        [
            'id' => 1002,
            'name' => 'Author #3',
            'description' => 'Description about Author #3',
            'location' => 'Manchester',
            'rating' => 4,
            'created' => '2019-03-27 13:12:00',
            'modified' => '2019-03-27 13:11:00',
        ],
    ];
}
