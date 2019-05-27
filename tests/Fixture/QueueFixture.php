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

class QueueFixture extends Fixture
{
    public $datasource = 'test';

    public $table = 'queue';

    public $schema = [
        'id' => [
          'type' => 'primaryKey',
          'limit' => 11,
          'default' => NULL,
          'null' => false,
          'key' => 'primary',
        ],
        'queue' => [
          'type' => 'string',
          'limit' => 80,
          'default' => NULL,
          'null' => false,
        ],
        'data' => [
          'type' => 'text',
          'default' => NULL,
          'null' => false,
        ],
        'status' => [
          'type' => 'string',
          'limit' => 40,
          'default' => NULL,
          'null' => false,
        ],
        'locked' => [
          'type' => 'boolean',
          'default' => 0,
          'null' => true,
        ],
        'tries' => [
          'type' => 'integer',
          'limit' => 1,
          'default' => 0,
          'null' => true,
        ],
        'scheduled' => [
          'type' => 'datetime',
          'default' => NULL,
          'null' => false,
        ],
        'created' => [
          'type' => 'datetime',
          'default' => NULL,
          'null' => false,
        ],
        'modified' => [
          'type' => 'datetime',
          'default' => NULL,
          'null' => false,
        ],
      ];
}