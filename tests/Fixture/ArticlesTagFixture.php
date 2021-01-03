<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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

class ArticlesTagFixture extends Fixture
{
    protected $schema = [
        'columns' => [
            'article_id' => ['type' => 'integer'],
            'tag_id' => ['type' => 'integer'],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary','column' => ['article_id','tag_id']],
        ],
    ];

    protected $records = [
        [
            'article_id' => 1000,
            'tag_id' => 1001,
        ],
        [
            'article_id' => 1000,
            'tag_id' => 1002,
        ],
        [
            'article_id' => 1001,
            'tag_id' => 1000,
        ],
        [
            'article_id' => 1002,
            'tag_id' => 1001,
        ],
    ];
}
