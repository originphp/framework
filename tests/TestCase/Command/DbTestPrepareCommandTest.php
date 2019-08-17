<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;

use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbTestPrepareCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp() : void
    {
        parent::setUp();
        $config = $this->config = ConnectionManager::config('test');
        $config['database'] = 'tmp123';
        ConnectionManager::config('test', $config);
    }
   
    public function testExecute()
    {
        $this->exec('db:test:prepare --type=php');
        $this->assertExitSuccess();
        $this->assertRegExp('/Executed ([0-9]) statements/', $this->output());
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        // ConnectionManager::execute('drop table migrations');
        ConnectionManager::drop('test');
        ConnectionManager::config('test', $this->config);
    }
}
