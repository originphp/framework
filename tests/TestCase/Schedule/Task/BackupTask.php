<?php
declare(strict_types = 1);
namespace Origin\Test\TestCase\Schedule\Task;

use Origin\Schedule\Task;
use Origin\Schedule\Schedule;

class BackupTask extends Task
{
    protected $name = 'backup task';
    protected $description = 'Daily backup job';

    protected function handle(Schedule $schedule): void
    {

        // ID: 86dc375026e0
        $event = $schedule->call(function () {
            echo 'Backing up';
            sleep(1);
            echo 'Backup completed';
        })->everyMinute()
            ->inBackground();
    }
}
