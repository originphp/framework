<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;

use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbTestPrepareCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function testExecute()
    {
        $this->exec('db:test:prepare');
        $this->assertExitSuccess();
        $this->assertOutputContains('Executed 2 statements');
    }

    public function shutdown()
    {
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE authors');
        $connection->execute('DROP TABLE posts');
    }
}
