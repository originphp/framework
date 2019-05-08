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

use Origin\Console\Exception\StopExecutionException;

class MockShell extends Shell
{
    public $description ='MockShell';

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
        parent::startup();
        $this->shell()->startupCalled = true;
    }
    public function shutdown()
    {
        parent::shutdown();
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

        $this->assertEquals('Mock', $shell->name);
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

        $shell->runCommand('dummy', []);
        $this->assertTrue($shell->startupCalled);
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

    public function testParseArguments()
    {
        $shell = new Shell($this->ConsoleOutput, $this->ConsoleInput);
        $shell->addOption('option1', [
            'name'=>'option1',
            'help'=>'Option #1',
            'value' => 'description'
            ]);
        $args = ['--option1=value1'];
        $shell->runCommand('non-existant', $args);
        $this->assertEquals('value1', $shell->params('option1'));

        $shell = new Shell($this->ConsoleOutput, $this->ConsoleInput);

        $shell->addOption('option2', [
            'name'=>'option2',
            'help'=>'Option #2',
            'short' => 'o',
            'value' => 'description'
            ]);
        $args = ['-o=value2'];
        $shell->runCommand('non-existant', $args);

        $this->assertEquals('value2', $shell->params('option2'));

        $shell = new Shell($this->ConsoleOutput, $this->ConsoleInput);
        $shell->addOption('option3', [
                'help'=>'Option #3',
                'short' => 'o3'
                ]);
        $args = ['-o3'];
        $shell->runCommand('non-existant', $args);
        
        $this->assertTrue($shell->params('option3'));

        $shell = new Shell($this->ConsoleOutput, $this->ConsoleInput);
        $shell->addOption('option4', [
                'help'=>'Option #4'
                ]);

        $args = ['--option4'];
        $shell->runCommand('non-existant', $args);
                
        $this->assertTrue($shell->params('option4'));

        // Test Params
        $this->assertNull($shell->params('option5'));
        $this->assertIsArray($shell->params());
        $shell->params('option6', 'value6');
        $this->assertEquals('value6', $shell->params('option6'));
        $shell->params(['key'=>'value']);
        $this->assertEquals('value', $shell->params('key'));
    }

    /**
     * This will fail because we are passing = with value and we
     * did not set the the value key
     *
     * @return void
     */
    public function testParseArgumentsException1()
    {
        $this->expectException(StopExecutionException::class);

        $shell = new Shell($this->ConsoleOutput, $this->ConsoleInput);
        $shell->addOption('option1', [
            'name'=>'option1',
            'help'=>'Option #1'
            ]);
        $args = ['--option1=value1'];
        $shell->runCommand('non-existant', $args);
    }

    /**
     * This will fail because we did not set the add option
     *
     * @return void
     */
    public function testParseArgumentsException2()
    {
        $this->expectException(StopExecutionException::class);

        $shell = new Shell($this->ConsoleOutput, $this->ConsoleInput);
        $args = ['--option1'];
        $shell->runCommand('non-existant', $args);
    }

    public function testArgs()
    {
        $shell = new Shell($this->ConsoleOutput, $this->ConsoleInput);
        $args = ['command1','command2'];
        $shell->runCommand('non-existant', $args);
        $this->assertIsArray($shell->args());
        $this->assertEquals('command1', $shell->args(0));
        $this->assertNull($shell->args(10));
    }

    public function testHelp()
    {
        $file = '/tmp/' . uniqid();
        $shell = new Shell(new ConsoleOutput('file://' . $file), $this->ConsoleInput);
        $shell->addOption('option1', [
            'name'=>'option1',
            'help'=>'Option #1'
            ]);
        $shell->addOption('option2', [
                'name'=>'option2',
                'help'=>'Option #2',
                'value' =>'description'
                ]);

        $shell->addCommand('my_action', ['help'=>'Description goes here']);
        $shell->runCommand('non-existant', []);
        $shell->help();
        $buffer = file_get_contents($file);
        $this->assertContains('Usage:', $buffer);
        $this->assertContains('--option2=description', $buffer);
        $this->assertContains('Option #2', $buffer);

        $this->assertContains('my_action', $buffer);
        $this->assertContains('Description goes here', $buffer);
    }
}
