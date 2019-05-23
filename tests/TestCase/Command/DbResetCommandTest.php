<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbResetCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;


    public function initialize()
    {
        $config = ConnectionManager::config('test');
        $config['database'] = 'dummy';
        ConnectionManager::config('dummy',$config);
    }
    public function testExecute(){
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE DATABASE dummy');
        $this->exec('db:reset --datasource=dummy');
        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` dropped');
        $this->assertOutputContains('Loading /var/www/vendor/originphp/originphp/tests/TestApp/db/schema.sql');
        $this->assertOutputContains('Executed 2 statements');
        $this->assertOutputContains('Loading /var/www/vendor/originphp/originphp/tests/TestApp/db/seed.sql');
        $this->assertOutputContains('Executed 3 statements');
    }

    public function shutdown(){
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP DATABASE IF EXISTS dummy');
    }

  
}