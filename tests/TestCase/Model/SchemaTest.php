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
use Origin\Core\Exception\Exception;

include_once DATABASE . DS . 'schema.php';

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateSql()
    {
        $schema = new \ApplicationSchema();
        $connection = ConnectionManager::get('test');

        $this->executeStatements($schema->createSql($connection));
    }

    /**
     * @depends testCreateSql
     */
    public function testCreateSqlInvalidForeignKeySettings()
    {
        $this->expectException(Exception::class);
        $schema = new \ApplicationSchema();
        $connection = ConnectionManager::get('test');

        $schema->schema('bookmarks')['constraints']['bookmarks_ibfk_1'] = ['key' => 'value'];

        $this->executeStatements($schema->createSql($connection));
    }
    /**
     * @depends testCreateSql
     */
    public function testDropSql()
    {
        $schema = new \ApplicationSchema();
        $connection = ConnectionManager::get('test');

        $this->executeStatements($schema->dropSql($connection));
    }
    /**
     * @depends testCreateSql
     */
    protected function executeStatements(array $statements)
    {
        $connection = ConnectionManager::get('test');
        $connection->transaction(function ($connection) use ($statements) {
            foreach ($statements as $statement) {
                $this->assertTrue($connection->execute($statement));
            }
        }, true);
    }
}
