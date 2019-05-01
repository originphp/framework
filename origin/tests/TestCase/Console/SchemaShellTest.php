<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Console;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;
class SchemaShellTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp(){
        parent::setUp();
      
    }

    public function testImport(){
        
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS rocks;');

        $this->exec('schema import rocks -ds=test'); // Schema shell overides datasource
        $this->assertExitSuccess();
        $this->assertOutputContains('<green>OK</green>');
    }

    public function testImportError(){
        
        $this->exec('schema import'); // the same bin/console cron daily
        $this->assertExitError();
        $this->assertOutputContains('config/schema/schema.sql not found');
    }

    public function testGenerate(){
        $filename = CONFIG . '/schema/rocks.php';
        if(file_exists($filename)){
            unlink($filename);
        }
        $this->exec('schema generate rocks -ds=test'); // Schema shell overides datasource
        $this->assertExitSuccess();
        $this->assertOutputContains('Generated schema for rocks');

        $this->assertTrue(file_exists($filename));
        $this->assertEquals('7f17f699deb9cbd4b7bcd07c5451ee70',md5(file_get_contents($filename)));
    }

    public function testCreate(){
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE rocks;');

        $this->exec('schema create rocks -ds=test'); // Schema shell overides datasource
        $this->assertExitSuccess();
        $this->assertOutputContains('rocks table created');
    }
}