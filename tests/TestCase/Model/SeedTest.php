<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

include_once DATABASE . DS . 'schema.php';
include_once DATABASE . DS . 'seed.php';

class SeedTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
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

    protected function tearDown(): void
    {
        $connection = ConnectionManager::get('test');
        $schema = new \ApplicationSchema();

        $connection->begin();
        $connection->disableForeignKeyConstraints();
        $this->executeStatements($schema->dropSql($connection));
        $connection->enableForeignKeyConstraints();
        $connection->commit();
    }

    protected function executeStatements(array $statements)
    {
        foreach ($statements as $sql) {
            ConnectionManager::get('test')->execute($sql);
        }
    }
}
