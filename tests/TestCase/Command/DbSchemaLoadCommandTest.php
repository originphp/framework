<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbSchemaLoadCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp() : void
    {
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

        $this->exec('db:schema:load --datasource=test ' . $this->getSchemaName());

        $this->assertExitSuccess();
        $this->assertOutputContains('Executed 2 statements');
    }

    public function testExecuteInvalidSQL()
    {
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE TABLE authors (id INT)');
        $this->exec('db:schema:load --datasource=test '. $this->getSchemaName());
        $this->assertExitError();
        $this->assertErrorContains('Executing query failed'); # Using normal output for this
    }

    public function testExecuteInvalidSchemaFile()
    {
        $this->exec('db:schema:load --datasource=test dummy');
        $this->assertExitError();
        $this->assertErrorContains('File ' . ROOT . '/tests/TestApp/db/dummy.sql not found'); # Using normal output for this
    }

    public function testExecuteInvalidDatasource()
    {
        $this->exec('db:schema:load --datasource=foo');
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
        $this->exec('db:schema:load --datasource=test MyPlugin.pschema');
        $this->assertExitError();
        $this->assertErrorContains('/plugins/my_plugin/db/pschema.sql');
    }

    public function testExecuteLoadPHPSchema()
    {
        $this->exec('db:schema:load --datasource=test --type=php migrations');
        $this->assertExitSuccess();

        $this->assertRegExp('/Executed (1|2) statements/', $this->output());
        ConnectionManager::get('test')->execute('DROP DATABASE IF EXISTS migrations');
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP DATABASE IF EXISTS posts');
    }
}
