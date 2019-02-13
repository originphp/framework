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

namespace Origin\Test\Console\Task;

use Origin\Console\Shell;
use Origin\Console\Task\Task;
use Origin\Console\ConsoleInput;
use Origin\Console\ConsoleOutput;

class MockConsoleOutput extends ConsoleOutput
{
    public function stream()
    {
        return $this->stream;
    }
}

class MockShell extends Shell
{
}

class MockTask extends Task
{
    public function getTasks()
    {
        return $this->_tasks;
    }
}

class DummyTask
{
    public $name = 'DummyTask';
}

class TaskTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->MockShell = new MockShell([], new MockConsoleOutput('php://memory'), new ConsoleInput());
        $this->MockTask = new MockTask($this->MockShell);
    }
    public function testConstruct()
    {
        $this->assertInstanceOf(
            'Origin\Console\Task\TaskRegistry',
            $this->MockTask->taskRegistry()
        );
    }
    /**
     * This will test task and tasks function which uses task function
     *
     * @return void
     */
    public function testLoadTasks()
    {
        $this->MockTask->loadTasks([
            'Apple',
            'Orange' => ['type'=>'Fruit']
        ]);
        $expected = [
            'Apple' => ['className'=>'AppleTask'],
            'Orange' => ['className'=>'OrangeTask','type'=>'Fruit'],
        ];

        $this->assertEquals($expected, $this->MockTask->getTasks());
    }

    public function testShell()
    {
        $this->assertEquals($this->MockShell, $this->MockTask->shell());
    }

    public function testOut()
    {
        $this->MockTask->out('Foo bar');

        $stream = $this->MockTask->shell()->output->stream();
        rewind($stream);
        $this->assertEquals("Foo bar\n", stream_get_contents($stream));
    }

    /**
     * @depends testLoadTasks
     *
     * @return void
     */
    public function testLoading()
    {
        $this->MockTask->taskRegistry()->set('Dummy', new DummyTask());
        $this->MockTask->loadTask('Dummy');
       
        $this->assertInstanceOf('Origin\Test\Console\Task\DummyTask', $this->MockTask->Dummy);
        $this->assertNull($this->MockTask->DoesNotExist);
    }
}
