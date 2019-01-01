<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

use Origin\Console\Shell;
use Origin\Console\ConsoleOutput;
use Origin\Console\Task\Task;
use Origin\Model\ModelRegistry;

class MockShell extends Shell
{
}

class MockModel
{
    public $name = 'MockModel';
}

class MockTask extends Task
{
    public function startup()
    {
        $this->shell()->startupCalled = true;
    }
    public function shutdown()
    {
        $this->shell()->shutdownCalled = true;
    }
}

class ShellTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        if (file_exists(TMP . DS . 'shelltest.txt')) {
            unlink(TMP . DS . 'shelltest.txt');
        }
        $this->ConsoleOutput = new ConsoleOutput(TMP . DS . 'shelltest.txt');
    }
    public function testConstruct()
    {
        $shell = new MockShell(array(), $this->ConsoleOutput);
        $this->assertNotEmpty($shell->taskRegistry());
        $this->assertInstanceOf('Origin\Console\Task\TaskRegistry', $shell->taskRegistry());
        $this->assertEquals('MockShell', $shell->name);
    }
    public function testLoadModel()
    {
        $mockModel = new MockModel();
        ModelRegistry::set('MockModel', $mockModel);
        $shell = new MockShell(array(), $this->ConsoleOutput);
        // Test load from Registry
        $this->assertEquals($mockModel, $shell->loadModel('MockModel'));

        // Test already loaded
        $this->assertEquals($mockModel, $shell->loadModel('MockModel'));
    }

    public function testLoadTask()
    {
        $shell = new MockShell(array(), $this->ConsoleOutput);
        $mockTask = new MockTask($shell);
        $shell->taskRegistry()->set('MockTask', $mockTask);
        $shell->loadTask('MockTask');
        $this->assertEquals($mockTask, $shell->MockTask);
    }

    public function testLoadTasks()
    {
        $shell = new MockShell(array(), $this->ConsoleOutput);
        $mockTask = new MockTask($shell);
        $shell->taskRegistry()->set('MockTask', $mockTask);
        $shell->loadTasks(['MockTask']);
        $this->assertEquals($mockTask, $shell->MockTask);
    }

    /**
     * @depends testLoadTask
     */
    public function testCallbacks()
    {
        $shell = new MockShell(array(), $this->ConsoleOutput);
        $mockTask = new MockTask($shell);

        $shell->taskRegistry()->set('MockTask', $mockTask);
        $shell->loadTask('MockTask');
        $shell->taskRegistry()->enable('MockTask');

        $shell->startupProcess();
        $this->assertTrue($shell->startupCalled);
        $shell->shutdownProcess();
        $this->assertTrue($shell->shutdownCalled);
    }

    public function testOut()
    {
        $shell = new MockShell(array(), $this->ConsoleOutput);
        $shell->out('Hello World!');
        $this->assertEquals("Hello World!\n", file_get_contents(TMP . DS . 'shelltest.txt'));
    }
}
