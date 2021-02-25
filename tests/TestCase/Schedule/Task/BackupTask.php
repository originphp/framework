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
        // problem with parallel builds and testing background
        $path = getcwd() . '/tmp';

        if (! is_dir($path)) {
            mkdir($path);
        }
        $this->tempName = $path . '/background-test';

        if (file_exists($this->tempName)) {
            unlink($this->tempName);
        }
    }

    public function tempName(): string
    {
        return $this->tempName;
    }

    protected function handle(Schedule $schedule): void
    {
        $tmp = $this->tempName;

        // ID: e0765fbac6c9
        $event = $schedule->call(function () use ($tmp) {
            $count = 0;
            if (file_exists($tmp)) {
                $count = (int) file_get_contents($tmp);
                $count++;
            }
            file_put_contents($tmp, (string) $count, LOCK_EX);
        })->everyMinute()->background();
    }
}
