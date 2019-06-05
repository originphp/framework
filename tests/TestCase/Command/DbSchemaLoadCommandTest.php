<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbSchemaLoadCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp(){
        // Create copy
        $ds = ConnectionManager::get('test');
        $ds->execute('DROP TABLE IF EXISTS authors');
        $ds->execute('DROP TABLE IF EXISTS posts');
    }

    public function testExecute(){
        $this->exec('db:schema:load --datasource=test');

        $this->assertExitSuccess();
        $this->assertOutputContains('Executed 2 statements');
    }

    public function testExecuteInvalidSQL(){
        $ds = ConnectionManager::get('test');
        $ds->execute('CREATE TABLE authors (id INT)');
        $this->exec('db:schema:load --datasource=test');
        $this->assertExitError();
        $this->assertErrorContains('Base table or view already exists: 1050 Table \'authors\' already exists'); # Using normal output for this
    }

    public function testExecuteInvalidSchemaFile(){
        $this->exec('db:schema:load --datasource=test dummy');
        $this->assertExitError();
        $this->assertErrorContains('File /var/www/vendor/originphp/framework/tests/TestApp/db/dummy.sql not found'); # Using normal output for this
    }

    public function testExecuteInvalidDatasource(){
        $this->exec('db:schema:load --datasource=foo');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found'); # Using normal output for this
    }

    /**
     * Test using Plugin.schema 
     *
     * @return void
     */
    public function testExecutePluginSchemaFile(){
        $this->exec('db:schema:load --datasource=test MyPlugin.pschema');
        $this->assertExitError();
        $this->assertErrorContains('/plugins/my_plugin/db/pschema.sql'); 
    }

  
}