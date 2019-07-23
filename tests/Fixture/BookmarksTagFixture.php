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

class BookmarksTagFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
        'bookmark_id' => [
            'type' => 'integer',
            'limit' => 11,
            'default' => null,
            'null' => false,
            'key' => 'primary',
        ],
        'tag_id' => [
            'type' => 'integer',
            'limit' => 11,
            'default' => null,
            'null' => false,
            'key' => 'primary',
        ],
    ];
}
