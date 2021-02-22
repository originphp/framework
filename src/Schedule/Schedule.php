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
declare(strict_types = 1);
namespace Origin\Schedule;

use Origin\Job\Job;

/**
 * Schedule your tasks using source control and PHP. This is new and is under development.
 *
 * Cron: * * * * * cd /var/www && bin/console scheduler:run
 *
 */
class Schedule
{
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;
    
    /**
     * @var array
     */
    private $events = [];

    /**
     * @var string
     */
    private $time;

    public function __construct(array $options = [])
    {
        $options += ['time' => 'now'];
        $this->time = $options['time'];
    }

    /**
     * Calls any callable, e.g. closure, class with __invoke
     *
     * @see https://www.php.net/manual/en/language.oop5.magic.php#object.invoke
     *
     * @param callable $callable
     * @param array $arguments
     * @return \Origin\Schedule\Event
     */
    public function call(callable $callable, array $arguments = []): Event
    {
        return $this->events[] = new Event('callable', $callable, $arguments);
    }

    /**
     * Schedules a command to be run
     *
     * @param string $command e.g 'bin/console email:send'
     * @param array $arguments e.g ['-c database','-f']
     * @return \Origin\Schedule\Event
     */
    public function command(string $command, array $arguments = []): Event
    {
        if ($arguments) {
            $command .= ' ' . implode(' ', $arguments);
        }

        return $this->events[] = new Event('command', $command);
    }

    /**
     * Schedule a job to be run
     *
     *
     * @param \Origin\Job\Job $job
     * @param array $arguments arguments that will be passed to the dispatch method
     * @return \Origin\Schedule\Event
     */
    public function job(Job $job, array $arguments = []): Event
    {
        return $this->events[] = new Event('job', $job, $arguments);
    }

    /**
     * Gets a list of schedule events
     *
     * @return array
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * Starts the dispatch process, executes the events that neeed to be run
     *
     * @return void
     */
    public function dispatch(): void
    {
        foreach ($this->events as $event) {
            if ($event->isDue($this->time) && (! $this->maintenanceMode() || $event->runsInMaintenanceMode())) {
                $event->execute();
            }
        }
    }

    /**
     * Check if app is in maintencemode
     *
     * @return boolean
     */
    private function maintenanceMode(): bool
    {
        return file_exists(tmp_path('maintenance.json'));
    }
}
