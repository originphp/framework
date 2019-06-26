<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbDropCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp()
    {
        // Create copy
        $config = ConnectionManager::config('test');
        $config['database'] = 'dummy';
        ConnectionManager::config('dummy', $config);
    }

    public function testExecute()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE DATABASE dummy');

        $this->exec('db:drop --datasource=dummy');
        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` dropped');
    }

    public function testExecuteInvalidDatasource()
    {
        $this->exec('db:drop --datasource=foo');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found');
    }

    public function testExecuteDatabaseDoesNotExist()
    {
        $this->exec('db:drop --datasource=dummy');
        $this->assertExitError();
        $this->assertOutputContains('Database `dummy` does not exist');
    }

    public function shutdown()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP DATABASE IF EXISTS dummy');
    }
}
