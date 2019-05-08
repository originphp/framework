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
use Origin\TestSuite\FixtureManager;
use Origin\TestSuite\TestTrait;

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
}

class FixtureManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadUnload()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();
       
        // Load/unload first time
        $FixtureManager->load($TestCase);
        $this->assertTrue($FixtureManager->loaded('Framework.Post'));
        $this->assertNull($FixtureManager->unload($TestCase));
  
        // Load/unload second time
        $FixtureManager->load($TestCase);
        $this->assertTrue($FixtureManager->loaded('Framework.Post'));
        $this->assertNull($FixtureManager->unload($TestCase));

        $FixtureManager->setDropTables('Framework.Post', true);
        $FixtureManager->load($TestCase);
        $FixtureManager->unload($TestCase);

        $FixtureManager->setDropTables('Framework.Post', false);
        $FixtureManager->unload($TestCase);

        // set with Set
    }

    public function testLoaded()
    {
        $FixtureManager = new MockFixtureManager();
        $TestCase = new MockTestCase();
       
        // Load/unload first time
        $FixtureManager->load($TestCase);
        $this->assertTrue($FixtureManager->loaded('Framework.Post'));
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
