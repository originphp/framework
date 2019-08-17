<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbCreateCommandTest extends \PHPUnit\Framework\TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp() : void
    {
        parent::setUp();
        $config = ConnectionManager::config('test');
        $config['database'] = 'dummy';
        ConnectionManager::config('dummy', $config);
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        ConnectionManager::drop('dummy'); // # PostgreIssues
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP DATABASE IF EXISTS dummy');
    }

    public function testExecuteMySQL()
    {
        if (ConnectionManager::get('test')->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }
        $this->exec('db:create --datasource=dummy');

        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` created');
    }

    public function testExecutePgSQL()
    {
        if (ConnectionManager::get('test')->engine() !== 'pgsql') {
            $this->markTestSkipped('This test is for pgsql');
        }
        $this->exec('db:create --datasource=dummy schema-pg');

        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` created');
    }

    public function testExecuteInvalidDatasource()
    {
        $this->exec('db:create --datasource=foo');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found');
    }

    public function testExecuteDatabaseAlreadyExists()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE DATABASE dummy');

        $this->exec('db:create --datasource=dummy');
        $this->assertExitError();
        $this->assertOutputContains('Database `dummy` already exists');
    }

    public function testDatasourceException()
    {
        $config = ConnectionManager::config('test');
        $config['database'] = '<invalid-database-name>';
        ConnectionManager::config('dummy', $config);
        $this->exec('db:create --datasource=dummy');
        $this->assertExitError();
        $this->assertErrorContains('DatasourceException');
    }

    public function shutdown()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP DATABASE IF EXISTS dummy');
    }
}
