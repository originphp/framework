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

namespace Origin\Test\Model;

use Origin\Model\Schema;

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    public function testToTable()
    {
        $Schema = new Schema();
        $data = array(
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
          'updated' => 'datetime',
      );

        $expected = 'CREATE TABLE articles (
 id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(255) NOT NULL,
 body TEXT,
 published INT DEFAULT 0 NOT NULL,
 created DATETIME,
 updated DATETIME
)';
        $this->assertEquals($expected, $Schema->createTable('articles', $data));

        $data = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'deal' => array(
          'type' => 'string',
          'length' => 80,
          'null' => false,
        ),
        'description' => 'text',
        'amount' => array(
          'type' => 'decimal',
        ),
        'created' => 'datetime',
        'updated' => 'datetime',
    );
        $expected = 'CREATE TABLE deals (
 id INT AUTO_INCREMENT PRIMARY KEY,
 deal VARCHAR(80) NOT NULL,
 description TEXT,
 amount DECIMAL(10,0),
 created DATETIME,
 updated DATETIME
)';
        $this->assertEquals($expected, $Schema->createTable('deals', $data));
    }
}
