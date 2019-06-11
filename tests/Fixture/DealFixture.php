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

class DealFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = array(
        'id' =>     [
            'type' => 'primaryKey',
            'limit' => 10,
            'default' => NULL,
            'null' => false,
            'key' => 'primary',
          ],
          'name' =>     [
            'type' => 'string',
            'limit' => 120,
            'default' => '',
            'null' => false,
          ],
          'amount' =>     [
            'type' => 'decimal',
            'default' => NULL,
            'null' => true,
            'precision' => '15',
            'scale' => 2,
          ],
          'close_date' =>  [
            'type' => 'date',
            'default' => NULL,
            'null' => true,
          ],
          'stage' =>     [
            'type' => 'string',
            'limit' => 150,
            'default' => NULL,
            'null' => false,
          ],
          'status' =>     [
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
          'confirmed' =>     [
            'type' => 'time',
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
          ],
     );
    
}
