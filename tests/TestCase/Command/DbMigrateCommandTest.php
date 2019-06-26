<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbMigrateCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Migration'];

    protected function tearDown() : void
    {
        parent::tearDown();
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP table IF EXISTS foo');
        $ds->execute('DROP table IF EXISTS bar');
        $ds->execute('DROP table IF EXISTS foobar');
    }

    public function testMigrate()
    {
        $this->exec('db:migrate --datasource=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('Migration Complete. 3 migrations in 0 ms');
    }

    /**
     * @depends testMigrate
     */
    public function testRollback()
    {
        $this->exec('db:migrate --datasource=test'); // Inject data

        $this->exec('db:migrate --datasource=test 20190520033225');
        $this->assertExitSuccess();
        $this->assertOutputContains('Rollback Complete. 3 migrations in 0 ms');
    }

    public function testNoMigrations()
    {
        $this->exec('db:migrate --datasource=test'); // Run Migrations
        $this->exec('db:migrate --datasource=test'); // Run Again (this time none)
        $this->assertExitSuccess();
        $this->assertErrorContains('No migrations found'); // Its a warning
    }

    public function testNoMigrationsRollback()
    {
        $this->exec('db:migrate --datasource=test'); // Inject data
        $this->exec('db:migrate --datasource=test 20190520033226'); // Rollback
        $this->exec('db:migrate --datasource=test 20190520033226'); // Now there should be no migrations
        $this->assertExitSuccess();
        $this->assertErrorContains('No migrations found');
    }

    public function testMigrateException()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE TABLE foo (id INT)');
        $this->exec('db:migrate --datasource=test'); // Inject data
        $this->assertExitError();
    }

    public function testMigrateRollbackException()
    {
        $this->exec('db:migrate --datasource=test'); // Inject data

        $ds = ConnectionManager::get('test');
        $ds->execute('DROP TABLE foo');

        $this->exec('db:migrate --datasource=test 20190520033225'); // Rollback
        $this->assertExitError();
    }
}
