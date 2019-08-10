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
        
        $this->executeStatements($schema->createSql($connection));
    }

    public function testDropSql()
    {
        $schema = new \ApplicationSchema();
        $connection = ConnectionManager::get('test');

        $this->executeStatements($schema->dropSql($connection));
    }

    protected function executeStatements(array $statements)
    {
        $connection = ConnectionManager::get('test');
        $connection->begin();
        $connection->disableForeignKeyConstraints();
        foreach ($statements as $statement) {
            $this->assertTrue($connection->execute($statement));
        }
        $connection->enableForeignKeyConstraints();
        $connection->commit();
    }
}
