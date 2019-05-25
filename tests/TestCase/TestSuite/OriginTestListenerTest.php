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

use Origin\TestSuite\OriginTestListener;
use Origin\TestSuite\FixtureManager;
use Origin\TestSuite\OriginTestCase;

class MockTestSuite extends \PHPUnit\Framework\TestSuite
{
}

class MockOriginTestCase extends OriginTestCase
{
}

class OriginTestListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testStartTestSuite()
    {
        $listener = new OriginTestListener();
        $listener->startTestSuite(new MockTestSuite());
        $this->assertInstanceOf(FixtureManager::class, $listener->fixtureManager);
    }

    public function testEndTestSuite()
    {
        $listener = new OriginTestListener();
        $testSuite = new MockTestSuite();
        
        $listener->startTestSuite($testSuite);
        $this->assertTrue(isset($listener->fixtureManager));
        $this->assertNull($listener->endTestSuite($testSuite));
    }

    public function testStartTest()
    {
        $listener = new OriginTestListener();

        $mockFixtureManager = $this->createMock(FixtureManager::class);
        $mockFixtureManager->expects($this->exactly(1))
                            ->method('load');

        $listener->fixtureManager = $mockFixtureManager;
        $listener->startTest(new MockOriginTestCase());

        $mockFixtureManager = $this->createMock(FixtureManager::class);
        $mockFixtureManager->expects($this->exactly(0))
                            ->method('load');

        $listener->fixtureManager = $mockFixtureManager;
        $listener->startTest(new MockTestSuite());
    }

    public function testEndTest()
    {
        $listener = new OriginTestListener();
        
        $mockFixtureManager = $this->createMock(FixtureManager::class);
        $mockFixtureManager->expects($this->exactly(1))
                            ->method('unload');

        $listener->fixtureManager = $mockFixtureManager;
        $listener->endTest(new MockOriginTestCase(), microtime(true));

        $mockFixtureManager = $this->createMock(FixtureManager::class);
        $mockFixtureManager->expects($this->exactly(0))
                            ->method('unload');

        $listener->fixtureManager = $mockFixtureManager;
        $listener->endTest(new MockTestSuite(), microtime(true));
    }
}
