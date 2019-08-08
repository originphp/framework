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

class ArticleFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
        'columns' => [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'author_id' => ['type' => 'integer'],
            'title' => ['type' => 'string','limit' => 255, 'null' => false],
            'body' => 'text',
            'created' => 'datetime',
            'modified' => 'datetime',
        ],
        'constraints' => [
            'primary' => ['type' => 'primary','column' => 'id'],
        ],
    ];

    public $records = [
        [
            'id' => 1000,
            'author_id' => 1001,
            'title' => 'Article #1',
            'body' => 'Description about article #1',
            'created' => '2019-03-27 13:10:00',
            'modified' => '2019-03-27 13:12:00',
        ],
        [
            'id' => 1001,
            'author_id' => 1000,
            'title' => 'Article #2',
            'body' => 'Description about article #2',
            'created' => '2019-03-27 13:11:00',
            'modified' => '2019-03-27 13:11:00',
        ],
        [
            'id' => 1002,
            'author_id' => 1000,
            'title' => 'Article #3',
            'body' => 'Description about article #3',
            'created' => '2019-03-27 13:12:00',
            'modified' => '2019-03-27 13:10:00',
        ],
        
    ];
}
