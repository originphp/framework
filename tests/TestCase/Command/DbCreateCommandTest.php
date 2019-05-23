<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbCreateCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp(){
        // Create copy
        $config = ConnectionManager::config('test');
        $config['database'] = 'dummy';
        ConnectionManager::config('dummy',$config);
    }

    public function testExecute(){
        $this->exec('db:create --datasource=dummy');
        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` created');
    }

    public function testExecuteInvalidDatasource(){
        $this->exec('db:create --datasource=foo');
        $this->assertExitError();
        $this->assertOutputContains('foo datasource not found');
    }

    public function testExecuteSQLException(){
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE DATABASE dummy');

        $this->exec('db:create --datasource=dummy');
        $this->assertExitError();
        $this->assertErrorContains('Can\'t create database \'dummy\'');
    }

    public function tearDown(){
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP DATABASE IF EXISTS dummy');
    }
}