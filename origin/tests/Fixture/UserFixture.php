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
         'id' => array('type' => 'integer', 'key' => 'primary'),
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
           'id' => 1,
           'name' => 'James',
           'email' => 'james@example.com',
           'password' => 'secret1',
           'created' => '2018-12-20 09:00:00',
           'created' => '2018-12-20 09:00:15',
         ),
         array(
           'id' => 2,
           'name' => 'Amanda',
           'email' => 'amanda@example.com',
           'password' => 'secret2',
           'created' => '2018-12-20 09:01:00',
           'created' => '2018-12-20 09:00:30',
         ),
     );
}
