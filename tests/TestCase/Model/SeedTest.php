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
    private function connection()
    {
        return ConnectionManager::get('test');
    }
    
    public function testInsertSql()
    {
        $this->executeStatements(
            (new \ApplicationSchema())->createSql($this->connection())
        );

        $this->executeStatements(
            (new \ApplicationSeed())->insertSql($this->connection())
        );

        $this->executeStatements(
            (new \ApplicationSchema())->dropSql($this->connection())
        );
    }

    protected function executeStatements(array $statements)
    {
        $connection = ConnectionManager::get('test');
        $connection->transaction(function ($connection) use ($statements) {
            foreach ($statements as $sql) {
                if (is_array($sql)) {
                    list($sql, $params) = $sql;
                    $this->assertTrue($connection->execute($sql, $params));
                } else {
                    $this->assertTrue($connection->execute($sql));
                }
            }
        }, true);
    }
}
