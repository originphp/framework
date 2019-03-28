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

class UserFixture extends Fixture
{
    public $datasource = 'test';

    public $fields = array(
         'id' => array('type' => 'integer', 'key' => 'primary','autoIncrement'=>true),
         'name' => array(
           'type' => 'string',
           'length' => 255,
           'null' => false,
         ),
         'email' => array(
           'type' => 'string',
           'length' => 255,
           'null' => false,
         ),
         'password' => array(
           'type' => 'string',
           'length' => 255,
           'null' => false,
         ),
         'created' => 'datetime',
         'modified' => 'datetime',
     );
    public $records = array(
         array(
           'id' => 1000,
           'name' => 'James',
           'email' => 'james@example.com',
           'password' => '$2y$10$V5RgkqQ6Onnxgz2rmEBJDuftS9DX7iD0qv8V3LlM0qDdTYK2Y3Fbq',
           'created' => '2018-12-20 09:00:00',
           'created' => '2018-12-20 09:00:15',
         ),
         array(
           'id' => 1001,
           'name' => 'Amanda',
           'email' => 'amanda@example.com',
           'password' => '$2y$10$YK3SO6y4O9ObgpLG6HH75e6o2uQFQxdQ3qbE8szwMCTpZxSao6H16',
           'created' => '2018-12-20 09:01:00',
           'created' => '2018-12-20 09:00:30',
         ),
     );
}
