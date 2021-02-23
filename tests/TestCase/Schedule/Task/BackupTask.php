<?php
declare(strict_types = 1);
namespace Origin\Test\TestCase\Schedule\Task;

use Origin\Schedule\Task;
use Origin\Schedule\Schedule;

class BackupTask extends Task
{
    protected $name = 'backup task';
    protected $description = 'Daily backup job';

    private $tempName;

    protected function initialize(): void
    {
        $this->tempName = sys_get_temp_dir() . '/schedule-background-test.tmp';
    }

    public function tempName(): string
    {
        return $this->tempName;
    }

    protected function handle(Schedule $schedule): void
    {
        if (file_exists($this->tempName)) {
            unlink($this->tempName);
        }

        $tmp = $this->tempName;

        // ID: 5546177403d8
        $event = $schedule->call(function () use ($tmp) {
            file_put_contents($tmp, (string) getmypid());
        })->everyMinute()
            ->inBackground();

        #debug($event->id());
    }
}
