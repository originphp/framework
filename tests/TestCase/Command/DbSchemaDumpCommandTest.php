<?php
namespace Origin\Test\Command;

use DumpSchema;
use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbSchemaDumpCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Post'];

    protected function deleteFile(string $filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    public function testDumpSQL()
    {
        $filename = APP . DS . 'database' . DS . 'dump.sql';
        $this->deleteFile($filename);

        $this->exec('db:schema:dump --connection=test --type=sql dump');
        $this->assertExitSuccess();
        $this->assertOutputContains('Dumping database `origin_test` schema to ' . ROOT . DS . 'tests' . DS . 'TestApp' . DS . 'database' . DS . 'dump.sql');
        $this->assertTrue(file_exists($filename));
        
        $this->assertOutputContains('* posts');

        $contents = file_get_contents($filename);
        // Different versions of MySQL also return different results, so test sample
      
        if (ConnectionManager::get('test')->engine() === 'mysql') {
            $this->assertContains('CREATE TABLE `posts` (', $contents);
            $this->assertContains('`title` varchar(255) NOT NULL,', $contents);
        } else { //pgsql
            $this->assertContains('CREATE TABLE "posts" (', $contents);
            $this->assertContains('"title" VARCHAR(255) NOT NULL,', $contents);
        }
    }

    public function testDumpSqlException()
    {
        $this->exec('db:schema:dump --connection=test --type=sql dump', ['n']);
        $this->assertExitError();
        $this->assertErrorContains('Error saving schema file');
    }

    public function testDumpPHP()
    {
        $filename = APP . DS . 'database' . DS . 'dump.php';
        $this->deleteFile($filename);
     
        $this->exec('db:schema:dump --connection=test --type=php dump');
    
        $this->assertExitSuccess();
        $this->assertOutputContains('Dumping database `origin_test` schema to ' . ROOT . DS . 'tests' . DS . 'TestApp' . DS . 'database' . DS . 'dump.php');
        $this->assertTrue(file_exists($filename));
        $this->assertOutputContains('* posts');
    
        // Check is valid object and some spot check
        include $filename;
        $schema = new DumpSchema();
        $this->assertInstanceOf(DumpSchema::class, $schema);
        $this->assertNotEmpty($schema->posts);
        $this->assertEquals('integer', $schema->posts['columns']['id']['type']);
        $this->assertNotEmpty($schema->posts['constraints']);
        $this->assertNotEmpty($schema->posts['constraints']['primary']);
    }

    public function testDumpPHPException()
    {
        $this->exec('db:schema:dump --connection=test --type=php dump', ['n']);
        $this->assertExitError();
        $this->assertErrorContains('Error saving schema file');
    }

    public function testDumpUnkownType()
    {
        $this->exec('db:schema:dump --connection=test --type=ruby');
        $this->assertExitError();
        $this->assertErrorContains('The type `ruby` is invalid');
    }

    public function testExecuteInvalidDatasource()
    {
        $this->exec('db:schema:dump --connection=foo');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found');
    }
}
