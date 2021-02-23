<?php
declare(strict_types = 1);
namespace Origin\Test\TestCase\Schedule\Task;

use Origin\Schedule\Task;
use Origin\Schedule\Schedule;

class MiscTask extends Task
{
    protected $name = 'misc task';
    protected $description = '';

    protected function handle(Schedule $schedule): void
    {
        // does not run
        $schedule->call(function () {
            $var = 'is never due';
        })->yearly();

        //
        $schedule->call(function () {
            $var = 'always runs in foreground';
        })->everyMinute();

        $schedule->call(function () {
            $var = 'always runs in background';
        })->everyMinute()->inBackground();
    }
}
