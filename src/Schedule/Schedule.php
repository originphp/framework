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
use ReflectionClass;
use InvalidArgumentException;
use Origin\Process\BackgroundProcess;
use Origin\Configurable\StaticConfigurable as Configurable;

/**
 * Schedule your tasks using source control and PHP. This is new and is under development.
 *
 * Cron: * * * * * cd /var/www && bin/console scheduler:run
 *
 */
class Schedule
{
    use Configurable;

    /**
     * Default Configuration
     *
     * @var array
     */
    protected static $defaultConfig = [
        'path' => null
    ];
    
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;
    
    /**
     * @var \Origin\Schedule\Task
     */
    private $task;

    /**
     * @var \Origin\Schedule\Event[]
     */
    private $events = [];

    public function __construct(Task $task)
    {
        $this->task = $task;
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
        $path = $this->getPath($this->task);

        foreach ($this->events as $event) {
            if (! $event->isDue('now')) {
                continue;
            }

            $config = $event->config();
  
            if ($this->maintenanceMode() && ! $config['maintenanceMode']) {
                continue;
            }

            for ($i = 0;$i < $config['instances'] ;$i++) {
                if ($config['background']) {
                    // debug(implode(' ', $this->buildCommand($path, $event->id())));

                    $process = new BackgroundProcess(
                        $this->buildCommand($path, $event->id())
                    );
                    
                    $process->start();
                } else {
                    $event->execute();
                }
            }
        }
    }

    /**
     * Gets the directory where object file is
     *
     * @param object $object
     * @return string
     */
    private function getPath(object $object): string
    {
        $reflection = new ReflectionClass($object);

        return pathinfo($reflection->getFilename(), PATHINFO_DIRNAME);
    }

    /**
     * Builds the command for the background process
     *
     * @param string $path
     * @param string $id
     * @return array
     */
    private function buildCommand(string $path, string $id): array
    {
        $schedulePath = $this->getPath($this);

        return [$schedulePath . '/bin/schedule:run',"--directory={$path}", "--id={$id}"];
    }

    /**
     * Runs
     *
     * @param string $path
     * @param string $eventId
     * @return void
     */
    public static function run(string $path, string $eventId = null): void
    {
        if (! is_dir($path)) {
            throw new InvalidArgumentException('Path does not exist');
        }

        if ($eventId) {
            static::runEvent($path, $eventId);
        } else {
            static::runEvents($path);
        }
    }

    /**
     * Runs all the events on all the tasks
     *
     * @param string $path
     * @return void
     */
    private static function runEvents(string $path): void
    {
        foreach (static::loadTasks($path) as $task) {
            $task->dispatch();
        }
    }

    /**
     * Runs a specific event in the tasks
     *
     * @param string $path
     * @param string $eventId
     * @return void
     */
    private static function runEvent(string $path, string $eventId): void
    {
        $event = static::findById($eventId, static::loadTasks($path));
      
        if (! $event) {
            throw new InvalidArgumentException('Invalid event ID');
        }
        $event->execute();
    }

    /**
     * @param string $id
     * @param array $tasks
     * @return \Origin\Schedule\Event|null
     */
    private static function findById(string $id, array $tasks): ?Event
    {
        foreach ($tasks as $task) {
            $task(); // invoke only
            foreach ($task->schedule()->events() as $event) {
                if ($event->id() === $id) {
                    return $event;
                }
            }
        }

        return null;
    }
  
    /**
     * Loads an array of Task objects
     *
     * @return array
     */
    private static function loadTasks(string $path): array
    {
        $out = [];

        foreach (scandir($path) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $class = static::parseClassName($path . '/' . $file);

                $task = new $class();
                if ($task instanceof Task) {
                    $out[] = $task;
                }
            }
        }

        return $out;
    }

    /**
     * @param string $path
     * @return string
     */
    private static function parseClassName(string $path): string
    {
        $class = substr(pathinfo($path, PATHINFO_BASENAME), 0, -4);
        $contents = file_get_contents($path);
        if (preg_match('#^namespace\s+(.+?);$#sm', $contents, $matches)) {
            $class = '\\' . $matches[1] . '\\' . $class;
        }

        return $class;
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
