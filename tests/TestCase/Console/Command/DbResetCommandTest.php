<?php
namespace Origin\Test\Console\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbResetCommandTest extends \PHPUnit\Framework\TestCase
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
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE DATABASE dummy;');
      
        $this->exec('db:reset --connection=dummy --type=sql');
        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` dropped');
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/database/schema.sql');
        $this->assertOutputContains('Executed 2 statements');
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/database/seed.sql');
        $this->assertOutputContains('Executed 3 statements');
    }

    public function testExecutePostgreSQL()
    {
        if (ConnectionManager::get('test')->engine() !== 'pgsql') {
            $this->markTestSkipped('This test is for pgsql');
        }
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE DATABASE dummy;');
      
        $this->exec('db:reset --connection=dummy --type=sql schema-pg');
        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` dropped');
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/database/schema-pg.sql');
        $this->assertOutputContains('Executed 2 statements');
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/database/seed.sql');
        $this->assertOutputContains('Executed 3 statements');
    }
}
