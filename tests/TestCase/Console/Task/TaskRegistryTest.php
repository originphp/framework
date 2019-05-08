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
use Origin\Console\Task\TaskRegistry;
use Origin\Console\ConsoleInput;
use Origin\Console\ConsoleOutput;
use Origin\Console\Exception\MissingTaskException;

class TaskRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->Shell = new Shell(new ConsoleOutput(), new ConsoleInput());
    }
    public function testLoad()
    {
        $taskRegistry = new TaskRegistry($this->Shell);
        $task = $taskRegistry->load('Task');
        $this->assertInstanceOf(Task::class, $task);
    }

    public function testThrowException()
    {
        $this->expectException(MissingTaskException::class);
        $taskRegistry = new TaskRegistry($this->Shell);
        $taskRegistry->load('TaskThatDoesNotExist');
    }
}
