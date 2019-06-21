<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class DbSchemaDumpCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function initialize()
    {
        $this->loadFixture('Origin.Post');
        
    }
    public function testDumpSQL(){

        $filename = APP . DS . 'db' . DS . 'dump.sql';
        if(file_exists($filename)){
            unlink($filename);
        }

        $this->exec('db:schema:dump --datasource=test dump');
        $this->assertExitSuccess();
        $this->assertOutputContains('Dumping schema to ' . ROOT . '/tests/TestApp/db/dump.sql');
        $this->assertTrue(file_exists($filename));
        $this->assertOutputContains('* posts');
        $this->assertEquals('9f8304de273ad7b3bde6649f17285f1d',md5(file_get_contents($filename)));

    }

    public function testDumpPHP(){

        $filename = APP . DS . 'db' . DS . 'dump.php';
        if(file_exists($filename)){
            unlink($filename);
        }

        $this->exec('db:schema:dump --datasource=test --type=php dump');
        $this->assertExitSuccess();
        $this->assertOutputContains('Dumping schema to ' . ROOT . '/tests/TestApp/db/dump.php');
        $this->assertTrue(file_exists($filename));
        $this->assertOutputContains('* posts');
     
        $this->assertEquals('6529ddda3735ac6266078961bc701a5a',md5(file_get_contents($filename)));
    }

    public function testDumpUnkownType(){
        $this->exec('db:schema:dump --datasource=test --type=ruby');
        $this->assertExitError();
        $this->assertErrorContains('The type `ruby` is invalid');
    }

    public function testExecuteInvalidDatasource(){
        $this->exec('db:schema:dump --datasource=foo');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found');
    }
  
}