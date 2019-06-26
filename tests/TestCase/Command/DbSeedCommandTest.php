<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbSeedCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function initialize()
    {
        $this->loadFixture('Origin.Post');
        $ds = ConnectionManager::get('test');
    }

    public function testExecute()
    {
        $this->exec('db:seed --datasource=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('Loading ' . ROOT . '/tests/TestApp/db/seed.sql');
        $this->assertOutputContains('Executed 3 statements');
    }
}
