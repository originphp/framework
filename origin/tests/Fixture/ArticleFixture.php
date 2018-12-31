<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Fixture;

use Origin\TestSuite\Fixture;

class ArticleFixture extends Fixture
{
    public $datasource = 'test';

    public $fields = array(
         'id' => array('type' => 'integer', 'key' => 'primary'),
         'title' => array(
           'type' => 'string',
           'length' => 255,
           'null' => false,
         ),
         'body' => 'text',
         'published' => array(
           'type' => 'integer',
           'default' => '0',
           'null' => false,
         ),
         'created' => 'datetime',
         'modified' => 'datetime',
     );

    public $records = array(
         array(
           'id' => 1,
           'title' => 'First Article',
           'body' => 'Article body goes here',
           'published' => '1',
           'created' => '2018-12-19 13:29:10',
           'modified' => '2018-12-19 13:30:20',
         ),
         array(
           'id' => 2,
           'title' => 'Second Article',
           'body' => 'Article body goes here',
           'published' => '1',
           'created' => '2018-12-19 13:31:30',
           'modified' => '2018-12-19 13:32:40',
         ),
         array(
           'id' => 3,
           'title' => 'Third Article',
           'body' => 'Third Article Body',
           'published' => '1',
           'created' => '2018-12-19 13:33:50',
           'modified' => '2018-12-19 13:34:59',
         ),
     );
}
