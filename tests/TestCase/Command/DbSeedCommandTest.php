<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbSeedCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Post'];
  
    public function testExecute()
    {
        $this->exec('db:seed --datasource=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/db/seed.sql');
        $this->assertOutputContains('Executed 3 statements');
    }
}
