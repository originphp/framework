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

use Origin\Console\Shell;
use Origin\Console\ConsoleInput;
use Origin\Console\ConsoleOutput;
use Origin\Console\Task\Task;
use Origin\Model\ModelRegistry;
use Origin\Model\Exception\MissingModelException;

class MockShell extends Shell
{
    public function initialize()
    {
        $this->addCommand('dummy');
        $this->addOption('bar');
        $this->addOption('foo', ['value'=>'name']);
    }
    public function publicMethod()
    {
    }
    protected function protectedMethod()
    {
    }
    private function privateMethod()
    {
    }

    public function dummy()
    {
    }
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

class DummyConsoleInput extends ConsoleInput
{
    protected $result = null;

    public function setResult($result = null)
    {
        $this->result = $result;
    }
    public function read()
    {
        return $this->result;
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
        $this->ConsoleInput = new ConsoleInput();
    }
    public function testConstruct()
    {
        $arguments = ['foo','--bar','--foo=setting'];
        $shell = new MockShell(new ConsoleOutput(), $this->ConsoleInput);

        $this->assertNotEmpty($shell->taskRegistry());
        $this->assertInstanceOf('Origin\Console\Task\TaskRegistry', $shell->taskRegistry());

        $shell->runCommand('dummy', $arguments);

        $this->assertEquals('MockShell', $shell->name);
        $this->assertEquals(['foo'], $shell->args);
        $this->assertEquals(['bar'=>true,'foo'=>'setting'], $shell->params);
    }
    public function testLoadModel()
    {
        $mockModel = new MockModel();
        ModelRegistry::set('MockModel', $mockModel);
        $shell = new MockShell($this->ConsoleOutput, $this->ConsoleInput);
        // Test load from Registry
        $this->assertEquals($mockModel, $shell->loadModel('MockModel'));

        // Test already loaded
        $this->assertEquals($mockModel, $shell->loadModel('MockModel'));

        $this->expectException(MissingModelException::class);
        $shell->loadModel('NonExistantModel');
    }

    public function testLoadTask()
    {
        $shell = new MockShell($this->ConsoleOutput, $this->ConsoleInput);
        $mockTask = new MockTask($shell);
        $shell->taskRegistry()->set('MockTask', $mockTask);
        $shell->loadTask('MockTask');
        $this->assertEquals($mockTask, $shell->MockTask);
    }

    /**
     * @depends testLoadTask
     */
    public function testCallbacks()
    {
        $shell = new MockShell($this->ConsoleOutput, $this->ConsoleInput);
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
        $shell = new MockShell($this->ConsoleOutput, $this->ConsoleInput);
        $shell->out('Hello World!');
        $this->assertEquals("Hello World!\n", file_get_contents(TMP . DS . 'shelltest.txt'));
    }

    public function testIn()
    {
        // Test result
        $ConsoleInput = new DummyConsoleInput();
        $ConsoleInput->setResult('y');
        $shell = new MockShell($this->ConsoleOutput, $ConsoleInput);
        $result = $shell->in('Enter a something', ['y','n']);
        $this->assertEquals('y', $result);

        // Test default
        $ConsoleInput->setResult('');
        $shell = new MockShell($this->ConsoleOutput, $ConsoleInput);
        $result = $shell->in('Enter a something', ['y','n'], 'n');
        $this->assertEquals('n', $result);
    }
    public function testIsAccessible()
    {
        $shell = new MockShell($this->ConsoleOutput, $this->ConsoleInput);
        $this->assertTrue($shell->isAccessible('publicMethod'));
        $this->assertFalse($shell->isAccessible('initialize'));
        $this->assertFalse($shell->isAccessible('protectedMethod'));
        $this->assertFalse($shell->isAccessible('privateMethod'));
        $this->assertFalse($shell->isAccessible('unkownMethod'));
    }
}
