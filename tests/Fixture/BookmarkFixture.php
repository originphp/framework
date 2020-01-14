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

class BookmarkFixture extends Fixture
{
    protected $schema = [
        'columns' => [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'user_id' => [
                'type' => 'integer',
                'limit' => 11,
                'default' => null,
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'limit' => 50,
                'default' => null,
                'null' => false,
            ],
            'description' => [
                'type' => 'text',
                'default' => null,
                'null' => true,
            ],
            'url' => [
                'type' => 'text',
                'default' => null,
                'null' => true,
            ],
            'category' => [
                'type' => 'string',
                'limit' => 80,
                'default' => null,
                'null' => true,
            ],
            'created' => [
                'type' => 'datetime',
                'default' => null,
                'null' => false,
            ],
            'modified' => [
                'type' => 'datetime',
                'default' => null,
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary','column' => 'id'],
        ],
    ];
}
