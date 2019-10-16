<?php
namespace Origin\Test\Console\Command;

use Origin\Model\ConnectionManager;

use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbTestPrepareCommandTest extends \PHPUnit\Framework\TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp() : void
    {
        $config = $this->config = ConnectionManager::config('test');
        $config['database'] = 'tmp123';
        ConnectionManager::config('test', $config);
    }
   
    public function testExecute()
    {
        $this->exec('db:test:prepare --type=php');
        $this->assertExitSuccess();
        $this->assertRegExp('/Executed ([1-9]) statements/', $this->output());
    }

    protected function tearDown() : void
    {
        /**
         * Clean up tables
         */
        
        $connection = ConnectionManager::get('test');
        foreach (['bookmarks', 'bookmarks_tags','tags','users'] as $table) {
            $sql = $connection->adapter()->dropTableSql($table, ['ifExists' => true]);
            $connection->execute($sql);
        }
     
        ConnectionManager::drop('test');
        ConnectionManager::config('test', $this->config);
    }
}
