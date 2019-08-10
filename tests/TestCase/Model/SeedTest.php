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

class SeedTest extends OriginTestCase
{
    public function initialize()
    {
        include_once APP . DS . 'db' . DS . 'schema.php';
        include_once APP . DS . 'db' . DS . 'seed.php';
    }

    public function startup()
    {
        $connection = ConnectionManager::get('test');
        $schema = new \ApplicationSchema();
        $this->executeStatements($schema->createSql($connection));
    }
    
    public function testInsertSql()
    {
        $connection = ConnectionManager::get('test');
        $seed = new \ApplicationSeed();
        $connection->begin();
        $connection->disableForeignKeyConstraints();
        
        foreach ($seed->insertSql($connection) as $query) {
            list($sql, $params) = $query;
            $this->assertTrue($connection->execute($sql, $params));
        }
        $connection->enableForeignKeyConstraints();
        $connection->commit();
    }

    public function shutdown()
    {
        $connection = ConnectionManager::get('test');
        $schema = new \ApplicationSchema();
        $this->executeStatements($schema->dropSql($connection));
    }

    protected function executeStatements(array $statements)
    {
        foreach ($statements as $sql) {
            ConnectionManager::get('test')->execute($sql);
        }
    }
}
