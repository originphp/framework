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

namespace Origin\Test\Model\Engine;

use Origin\Model\ConnectionManager;

/**
 * @todo migrate from model tests here, since this object was created and tests
 * were not moved
 */
class PgsqlEngineTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $connection = ConnectionManager::get('test');
        if ($connection->engine() !== 'pgsql') {
            $this->markTestSkipped('This test is for pgSQL');
        }
    }

    public function testTables(): void
    {
        $connection = ConnectionManager::get('test');
        $tables = $connection->tables();
        $this->assertIsArray($tables);
    }

    public function testDatabases(): void
    {
        $connection = ConnectionManager::get('test');
        $databases = $connection->databases();
        $this->assertContains('origin', $databases);
        $this->assertContains('origin_test', $databases);
    }
}
