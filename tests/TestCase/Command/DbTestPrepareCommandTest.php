<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;

use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbTestPrepareCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function startup()
    {
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

    public function shutdown()
    {
        // ConnectionManager::execute('drop table migrations');
        ConnectionManager::drop('test');
        ConnectionManager::config('test', $this->config);
    }
}
