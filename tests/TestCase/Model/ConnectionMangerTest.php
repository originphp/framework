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
use Origin\Model\Datasource;
use Origin\Model\Exception\MissingDatasourceException;

class ConnectionManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $this->assertInstanceOf(Datasource::class, ConnectionManager::get('default'));
    }
    public function testGetException()
    {
        $this->expectException(MissingDatasourceException::class);
        ConnectionManager::get('foo');
    }

    public function testHas()
    {
        $this->assertTrue(ConnectionManager::has('default'));
        $this->assertFalse(ConnectionManager::has('foo'));
    }
}