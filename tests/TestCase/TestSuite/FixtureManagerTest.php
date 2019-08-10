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

namespace Origin\Test\TestSuite;

use Origin\TestSuite\Fixture;
use Origin\Exception\Exception;
use Origin\TestSuite\TestTrait;
use Origin\Test\Fixture\PostFixture;
use Origin\TestSuite\FixtureManager;

class MockTestCase
{
    public $fixtures = ['Framework.Post'];
}

class MockFixtureManager extends FixtureManager
{
    use TestTrait;

    public function setDropTables($fixture, $value)
    {
        $this->loaded[$fixture]->dropTables = $value;
    }

    public function setSchema(string $fixture, array $schema)
    {
        $this->loaded[$fixture]->schema = $schema;
    }
    public function setRecords(string $fixture, array $records)
    {
        $this->loaded[$fixture]->records = $records;
    }
}

class FixtureManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadFixtureDropTables()
    {
        // Create a stub for the SomeClass class.
        $stub = $this->createMock(PostFixture::class);
       
        $stub->dropTables = true;
        $stub->expects($this->once())->method('drop');
        $stub->expects($this->once())->method('create');
        $stub->expects($this->once())->method('insert');
        $stub->expects($this->never())->method('truncate');

        $fixtureManager = new MockFixtureManager();
        $fixtureManager->setProperty('loaded', ['Framework.Post' => $stub]);

        $fixtureManager->loadFixture('Framework.Post');
    }

    public function testLoadFixtureDontDropTables()
    {
        // Create a stub for the SomeClass class.
        $stub = $this->createMock(PostFixture::class);
       
        $stub->dropTables = false;
        $stub->expects($this->never())->method('drop');
        $stub->expects($this->never())->method('create');
        $stub->expects($this->once())->method('insert');
        $stub->expects($this->once())->method('truncate');
 
        $fixtureManager = new MockFixtureManager();
        $fixtureManager->setProperty('loaded', ['Framework.Post' => $stub]);
 
        $fixtureManager->loadFixture('Framework.Post');
    }

    public function testCreateTableException()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();

        // Load and Unload
        $FixtureManager->load($TestCase);
        $FixtureManager->unload($TestCase);

        // Error creating Framework.Post fixture for test case Origin\Test\TestSuite\MockTestCase
        $this->expectException(Exception::class);
        $FixtureManager->setSchema('Framework.Post', [
            'columns' => [
                'id' => 'integer',
                'text' => 'POSTTITLE(1234)',
            ],
        ]);
        $FixtureManager->load($TestCase);
    }

    public function testInsertRecordsException()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();

        // Load and Unload
        $FixtureManager->load($TestCase);
        $FixtureManager->unload($TestCase);

        // Error inserting records in Framework.Post fixture for test case Origin\Test\TestSuite\MockTestCase
        $this->expectException(Exception::class);
        $FixtureManager->setRecords('Framework.Post', [
            ['id' => 'abc'],
        ]);
        $FixtureManager->load($TestCase);
    }

    public function testLoadUnload()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();
       
        // Load/unload first time
        $FixtureManager->load($TestCase);
        $this->assertInstanceOf(PostFixture::class, $FixtureManager->loaded('Framework.Post'));
        $this->assertNull($FixtureManager->unload($TestCase));
  
        // Load/unload second time
        $FixtureManager->load($TestCase);
        $this->assertInstanceOf(PostFixture::class, $FixtureManager->loaded('Framework.Post'));
        $this->assertNull($FixtureManager->unload($TestCase));

        $FixtureManager->setDropTables('Framework.Post', true);
        $FixtureManager->load($TestCase);
        $FixtureManager->unload($TestCase);

        $FixtureManager->setDropTables('Framework.Post', false);
        $FixtureManager->load($TestCase);
        $FixtureManager->unload($TestCase);

        // set with Set
        $FixtureManager->shutdown();
    }

    public function testLoaded()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();
       
        // Load/unload first time
        $FixtureManager->load($TestCase);
        $this->assertInstanceOf(PostFixture::class, $FixtureManager->loaded('Framework.Post'));
        $loaded = $FixtureManager->loaded();
        $this->assertInstanceOf(Fixture::class, $loaded['Framework.Post']);
    }

    public function testResolveFixture()
    {
        $FixtureManager = new MockFixtureManager();

        $result = $FixtureManager->callMethod('resolveFixture', ['App.Post']);
        $this->assertEquals('App\Test\Fixture\PostFixture', $result);

        $result = $FixtureManager->callMethod('resolveFixture', ['Framework.Contact']);
        $this->assertEquals('Origin\Test\Fixture\ContactFixture', $result);

        $result = $FixtureManager->callMethod('resolveFixture', ['PluginName.Lead']);
        $this->assertEquals('PluginName\Test\Fixture\LeadFixture', $result);
    }
}
