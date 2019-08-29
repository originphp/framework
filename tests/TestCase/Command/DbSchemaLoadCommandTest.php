<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbSchemaLoadCommandTest extends \PHPUnit\Framework\TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp() : void
    {
        parent::setUp();
        // Create copy
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP TABLE IF EXISTS authors');
        $ds->execute('DROP TABLE IF EXISTS posts');
    }
    protected function getSchemaName()
    {
        $engine = ConnectionManager::get('test')->engine();
        if ($engine === 'pgsql') {
            return 'schema-pg';
        }

        return 'schema';
    }
    public function testExecute()
    {
        $name = $this->getSchemaName();

        $this->exec('db:schema:load --datasource=test --type=sql ' . $this->getSchemaName());
   
        $this->assertExitSuccess();
        $this->assertOutputContains('Executed 2 statements');
    }

    public function testExecuteInvalidSQL()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE TABLE authors (id INT)');
   
        $this->exec('db:schema:load --datasource=test --type=sql '. $this->getSchemaName());
    
        $this->assertExitError();
        $this->assertErrorContains('Executing query failed'); # Using normal output for this
    }

    public function testExecuteInvalidSchemaFile()
    {
        $this->exec('db:schema:load --datasource=test --type=sql dummy');
        $this->assertExitError();
        $this->assertErrorContains('File ' . ROOT . '/tests/TestApp/database/dummy.sql not found'); # Using normal output for this
    }

    public function testExecuteInvalidDatasource()
    {
        $this->exec('db:schema:load --datasource=foo --type=sql');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found'); # Using normal output for this
    }

    /**
     * Test using Plugin.schema
     *
     * @return void
     */
    public function testExecutePluginSchemaFile()
    {
        $this->exec('db:schema:load --datasource=test --type=sql MyPlugin.pschema');
        $this->assertExitError();
        $this->assertErrorContains('/plugins/my_plugin/database/pschema.sql');
    }

    public function testExecuteLoadPHPSchema()
    {
        $this->exec('db:schema:load --datasource=test --type=php migrations');
        $this->assertExitSuccess();

        $this->assertRegExp('/Executed (1|2) statements/', $this->output());
        ConnectionManager::get('test')->execute('DROP TABLE IF EXISTS migrations');
    }

    public function testLoadUnkownType()
    {
        $this->exec('db:schema:load --datasource=test --type=ruby');
        $this->assertExitError();
        $this->assertErrorContains('The type `ruby` is invalid');
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP TABLE IF EXISTS posts');
    }
}
