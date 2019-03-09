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

use Origin\Console\Task\StatusTask;
use Origin\Console\Shell;
use Origin\Console\Task\Task;
use Origin\Console\ConsoleInput;
use Origin\Console\ConsoleOutput;

class MockStatusTask extends StatusTask
{
    public $buffer = null;
    public function out(string $data, $newLine = true)
    {
        $this->buffer = $data;
    }
}

class StatusTaskTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->Shell = new Shell([], new ConsoleOutput('php://memory'), new ConsoleInput());
        $this->StatusTask = new MockStatusTask($this->Shell);
    }


    public function testOk()
    {
        $expected = '<white>[</white> <green>OK</green> <white>]</white> <white>file copied</white>';
        $this->StatusTask->ok('file copied');
        $this->assertEquals($expected, $this->StatusTask->buffer);
    }
    public function testError()
    {
        $expected = '<white>[</white> <red>ERROR</red> <white>]</white> <white>file copied</white>';
        $this->StatusTask->error('file copied');
        $this->assertEquals($expected, $this->StatusTask->buffer);
    }

    public function testCustom()
    {
        $expected = '<white>[</white> <yellow>SKIPPED</yellow> <white>]</white> <white>file copied</white>';
        $this->StatusTask->custom('SKIPPED', 'yellow', 'file copied');
        $this->assertEquals($expected, $this->StatusTask->buffer);
    }
}
