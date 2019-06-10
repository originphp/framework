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

class BookmarkFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
        'id' =>     [
            'type' => 'primaryKey',
            'limit' => 11,
            'default' => NULL,
            'null' => false,
            'key' => 'primary',
          ],
          'user_id' =>     [
            'type' => 'integer',
            'limit' => 11,
            'default' => NULL,
            'null' => false,
          ],
          'title' =>     [
            'type' => 'string',
            'limit' => 50,
            'default' => NULL,
            'null' => false,
          ],
          'description' =>     [
            'type' => 'text',
            'default' => NULL,
            'null' => true,
          ],
          'url' =>     [
            'type' => 'text',
            'default' => NULL,
            'null' => true,
          ],
          'category' =>     [
            'type' => 'string',
            'limit' => 80,
            'default' => NULL,
            'null' => true,
          ],
          'created' =>     [
            'type' => 'datetime',
            'default' => NULL,
            'null' => false,
          ],
          'modified' =>     [
            'type' => 'datetime',
            'default' => NULL,
            'null' => false,
          ]
    ];

   
}