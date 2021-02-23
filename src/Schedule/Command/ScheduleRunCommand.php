<?php
declare(strict_types = 1);
namespace Origin\Schedule\Command;

use Origin\Schedule\Schedule;
use Origin\Console\Command\Command;

class ScheduleRunCommand extends Command
{
    protected $name = 'schedule:run';
    protected $description = 'Runs the scheduled tasks';

    protected function initialize(): void
    {
        $this->addOption('directory', [
            'description' => 'The directory where the tasks files are'
        ]);

        $this->addOption('id', [
            'description' => 'A specific event ID that should be run'
        ]);
    }
 
    protected function execute(): void
    {
        $path = $this->options('directory') ?: Schedule::config('path');

        if (is_null($path)) {
            $path = (defined('ROOT') ? ROOT : getcwd()) . '/app/Task';
        }

        if (! is_dir($path)) {
            $this->throwError('Directory does not exist');
        }
  
        Schedule::run($path, $this->options('id'));
    }
}
