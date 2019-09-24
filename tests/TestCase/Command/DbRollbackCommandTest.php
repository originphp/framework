<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbRollbackCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Migration'];

    public function testRollback()
    {
        // Load the Migrations from file
        $this->exec('db:migrate --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('Migration Complete. 3 migrations in 0 ms');

        $this->exec('db:rollback --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('Rollback Complete. 1 migrations in 0 ms');

        $this->exec('db:rollback --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('Rollback Complete. 1 migrations in 0 ms');

        $this->exec('db:rollback --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('Rollback Complete. 1 migrations in 0 ms');
    }

    public function testNoMigrations()
    {
        $this->exec('db:rollback --connection=test');
        $this->assertExitSuccess();
        $this->assertErrorContains('No migrations found'); // Its a warning
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP table IF EXISTS foo');
        $ds->execute('DROP table IF EXISTS bar');
        $ds->execute('DROP table IF EXISTS foobar');
    }
}
