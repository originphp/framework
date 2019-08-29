<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbSeedCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Post'];
  
    public function testExecute()
    {
        $this->exec('db:seed --datasource=test --type=sql');
        $this->assertExitSuccess();
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/database/seed.sql');
        $this->assertOutputContains('Executed 3 statements');
    }

    public function testExecuteArgumentName()
    {
        $this->exec('db:seed --datasource=test --type=sql seed');
        $this->assertExitSuccess();
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/database/seed.sql');
    }
    
    public function testExecuteArgumentNameFileNotExists()
    {
        $this->exec('db:seed --datasource=test --type=sql MyPlugin.records');
        $this->assertExitError();
        $this->assertErrorContains('my_plugin/database/records.sql not found'); // check plugin name as well
    }
}
