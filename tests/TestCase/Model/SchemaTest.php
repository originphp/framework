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

namespace Origin\Test\Model;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;

class SchemaTest extends OriginTestCase
{
    public function initialize()
    {
        include_once APP . DS . 'db' . DS . 'schema.php';
    }
    public function testCreateSql()
    {
        $schema = new \ApplicationSchema();
        $connection = ConnectionManager::get('test');
        
        foreach ($schema->createSql($connection) as $statement) {
            $this->assertTrue($connection->execute($statement));
        }
    }

    public function testDropSql()
    {
        $schema = new \ApplicationSchema();
        $connection = ConnectionManager::get('test');

        $connection->disableForeignKeyConstraints();

        foreach ($schema->dropSql($connection) as $statement) {
            $this->assertTrue($connection->execute($statement));
        }
        
        $connection->enableForeignKeyConstraints();
    }
}
