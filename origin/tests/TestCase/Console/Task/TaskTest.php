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
use Origin\Console\Task\Task;
use Origin\Console\ConsoleOutput;

class MockShell extends Shell
{
}

class MockTask extends Task
{
    public $output = null;
    public function out(string $data, $newLine = true)
    {
        if ($newLine) {
            $data .= "\n";
        }
        $this->output = $data;
    }
}

class TaskTest extends \PHPUnit\Framework\TestCase
{
    public function testShell()
    {
        $shell = new MockShell([], new ConsoleOutput());
        $task = new MockTask($shell);
        $this->assertEquals($shell, $task->shell());
    }

    public function testOut()
    {
        $shell = new MockShell([], new ConsoleOutput());
        $task = new MockTask($shell);
        $task->out('Foo bar');
        $this->assertEquals("Foo bar\n", $task->output);
    }
}
