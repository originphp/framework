<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbDropCommandTest extends OriginTestCase
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

    public function testExecute()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE DATABASE dummy');

        $this->exec('db:drop --connection=dummy');
        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` dropped');
    }

    public function testExecuteInvalidDatasource()
    {
        $this->exec('db:drop --connection=foo');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found');
    }

    public function testExecuteDatabaseDoesNotExist()
    {
        $this->exec('db:drop --connection=dummy');
        $this->assertExitError();
        $this->assertOutputContains('Database `dummy` does not exist');
    }
}
