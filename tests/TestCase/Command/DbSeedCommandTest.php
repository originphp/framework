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

    public function testExecuteSkipped()
    {
        $seed = ROOT . '/tests/TestApp/db/seed.sql';
        $contents = file_get_Contents($seed);
        unlink($seed);
        $this->exec('db:seed --datasource=test');
        file_put_contents($seed, $contents);
        $this->assertOutputContains('SKIPPED');
    }

    public function testExecuteArgumentName()
    {
        $this->exec('db:seed --datasource=test seed');
        $this->assertExitSuccess();
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/db/seed.sql');
    }
    public function testExecuteArgumentNameFileNotExists()
    {
        $this->exec('db:seed --datasource=test MyPlugin.records');
        $this->assertExitError();
        $this->assertErrorContains('plugins/my_plugin/db/records.sql` could not be found'); // check plugin name as well
    }
}
