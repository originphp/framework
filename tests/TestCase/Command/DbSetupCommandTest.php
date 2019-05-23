<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbSetupCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;


    public function initialize()
    {
        $config = ConnectionManager::config('test');
        $config['database'] = 'dummy';
        ConnectionManager::config('dummy',$config);
    }
    public function testExecute(){
        $this->exec('db:setup --datasource=dummy');
        $this->assertExitSuccess();
        $this->assertOutputContains('Database `dummy` created');
        $this->assertOutputContains('Loading /var/www/vendor/originphp/originphp/tests/TestApp/db/schema.sql');
        $this->assertOutputContains('Executed 2 statements');
        $this->assertOutputContains('Loading /var/www/vendor/originphp/originphp/tests/TestApp/db/seed.sql');
        $this->assertOutputContains('Executed 3 statements');
    }

    public function testExecutePluginPath(){
        $this->exec('db:setup --datasource=dummy MyPlugin.pschema');
        $this->assertExitError();
        $this->assertOutputContains('/var/www/vendor/originphp/originphp/tests/TestApp/plugins/my_plugin/db/pschema.sql');
    }

    public function shutdown(){
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP DATABASE IF EXISTS dummy');
    }

  
}