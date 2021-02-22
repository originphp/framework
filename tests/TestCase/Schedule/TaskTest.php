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
    protected function handle(Schedule $schedule): void
    {
        $schedule->command('ls -lah')
            ->everyMinute();
    }
}

class TaskTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Not much to test here other than there are no errors thrown
     */
    public function testDispatch()
    {
        $schedule = new Schedule();
        $task = new DummyTask();
        $result = $task->dispatch($schedule);
        $this->assertNull($result);
    }
}
