<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Test\Schedule;

use Origin\Schedule\Task;
use Origin\Schedule\Schedule;

class DummyTask extends Task
{
    public $initialized = false;
    public $startup = false;
    public $shutdown = false;

    protected function initialize(): void
    {
        $this->initialized = true;
    }
    protected function startup()
    {
        $this->startup = true;
    }
    public function handle(Schedule $schedule): void
    {
        $schedule->command('ls -lah')
            ->everyMinute();
    }

    protected function shutdown()
    {
        $this->shutdown = true;
    }
}

class TaskTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoke()
    {
        $task = new DummyTask(new Schedule);
        $result = $task();
        $this->assertTrue($task->initialized);
        $this->assertFalse($task->startup);
        $this->assertFalse($task->shutdown);
    }

    public function testSchedule()
    {
        $task = new DummyTask(new Schedule);
        $this->assertInstanceOf(Schedule::class, $task->schedule());
    }

    public function testDispatch()
    {
        $task = new DummyTask(new Schedule);

        $this->assertTrue($task->initialized);
        $this->assertFalse($task->startup);
        $this->assertFalse($task->shutdown);

        $result = $task->dispatch();

        $this->assertTrue($task->startup);
        $this->assertTrue($task->shutdown);
        $this->assertInstanceOf(Schedule::class, $task->schedule());
    }
}
