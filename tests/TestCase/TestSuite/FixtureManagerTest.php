<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
use Origin\TestSuite\TestTrait;
use Origin\Core\Exception\Exception;
use Origin\Test\Fixture\PostFixture;
use Origin\TestSuite\FixtureManager;

class MockTestCase
{
    protected $fixtures = ['Framework.Post'];

    public function fixtures() : array
    {
        return $this->fixtures;
    }
}

class MockPostFixture extends PostFixture
{
    use TestTrait;
}

class MockFixtureManager extends FixtureManager
{
    use TestTrait;

    public function setDropTables($fixture, $value)
    {
        $this->loaded[$fixture]->dropTables($value);
    }

    public function setSchema(string $fixture, array $schema)
    {
        $this->loaded[$fixture]->schema = $schema;
    }
    public function setRecords(string $fixture, array $records)
    {
        $this->loaded[$fixture]->records = $records;
    }

    public function setFixture($name, $fixture)
    {
        $this->loaded[$name] = $fixture;
    }
}

class FixtureManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadFixtureDropTables()
    {
        // Create a stub for the SomeClass class.
        $stub = $this->createMock(PostFixture::class);
       
        $stub->expects($this->any())
            ->method('dropTables')
            ->will($this->returnValue(true));
   
        $stub->expects($this->once())->method('drop');
        $stub->expects($this->once())->method('create');
        $stub->expects($this->once())->method('insert');
        $stub->expects($this->never())->method('truncate');

        $fixtureManager = new MockFixtureManager();
        $fixtureManager->setProperty('loaded', ['Framework.Post' => $stub]);

        $fixtureManager->load(new MockTestCase());
    }

    public function testLoadFixtureDontDropTables()
    {
        // Create a stub for the SomeClass class.
        $stub = $this->createMock(PostFixture::class);
       
        $stub->expects($this->any())
            ->method('dropTables')
            ->will($this->returnValue(false));

        $stub->expects($this->never())->method('drop');
        $stub->expects($this->never())->method('create');
        $stub->expects($this->once())->method('insert');
        $stub->expects($this->once())->method('truncate');
 
        $fixtureManager = new MockFixtureManager();
        $fixtureManager->setProperty('loaded', ['Framework.Post' => $stub]);
 
        $fixtureManager->load(new MockTestCase());
    }

    public function testCreateTableException()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();

        // Create bad fixture
        $PostFixture = new MockPostFixture;
        $PostFixture->setProperty('schema', [
            'columns' => [
                'id' => 'integer',
                'text' => 'POSTTITLE(1234)',
            ],
        ]);

        // Load and Unload
        $FixtureManager->load($TestCase);
        $FixtureManager->unload($TestCase);
        
        // Error creating Framework.Post fixture for test case Origin\Test\TestSuite\MockTestCase
        $this->expectException(Exception::class);
        $FixtureManager->setFixture('Framework.Post', $PostFixture);
        /* $FixtureManager->setSchema('Framework.Post', [
             'columns' => [
                 'id' => 'integer',
                 'text' => 'POSTTITLE(1234)',
             ],
         ]);*/
        $FixtureManager->load($TestCase);
    }

    public function testInsertRecordsException()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();
        $PostFixture = new MockPostFixture;
        $PostFixture->setProperty('records', [
            ['id' => 'abc'],
        ]);

        // Load and Unload
        $FixtureManager->load($TestCase);
        $FixtureManager->unload($TestCase);

        // Error inserting records in Framework.Post fixture for test case Origin\Test\TestSuite\MockTestCase
        $this->expectException(Exception::class);
        $FixtureManager->setFixture('Framework.Post', $PostFixture);
        /*$FixtureManager->setRecords('Framework.Post', [
            ['id' => 'abc'],
        ]);*/
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
