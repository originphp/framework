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

use Closure;
use SplFileObject;
use LogicException;
use Origin\Lock\Lock;
use RuntimeException;
use ReflectionFunction;
use InvalidArgumentException;
use Origin\Process\BackgroundProcess;

class Event
{
    /**
     * @var string command|job|callable
     */
    private $type;

    /**
     * This is the command string, job object or callable
     *
     * @var mixed
     */
    private $data;

    /**
     * Arguments
     *
     * @var array
     */
    private $arguments;

    /**
     * Path to where output should go. This can also be /dev/null so that cron does not email
     * errors or information.
     *
     * TODO: should this be default /dev/null ?
     *
     * @var string
     */
    private $output;

    /**
     * Append mode flag
     *
     * @var boolean
     */
    private $appendOutput = false;

    /**
     * If a command should run in the background
     *
     * @var boolean
     */
    private $background = false;

    /**
     * This event should run even if in maintence mode
     *
     * @var boolean
     */
    private $runInMaintenanceMode = false;

    /**
     * Restricts the maximum number of processes of this event running at the same time
     *
     * @var int
     */
    private $max = 0;

    /**
     * How many times this event will be executed
     *
     * @var integer
     */
    private $processes = 1;

    /**
     * Callables that will be called before the event is executed
     *
     * @var \callable[] $beforeCalbacks
     */
    private $beforeCallbacks = [];

    /**
     * Callables that will be called before the event is executed
     *
     * @var \callable[] $afterCalbacks
     */
    private $afterCallbacks = [];

    /**
     * Used by filters
     *
     * @var \callable[] $filters
     */
    private $filters = [];

    /**
     * Used by skip
     *
     * @var \callable[] $rejects
     */
    private $rejects = [];

    /**
     * Internal ID for event
     *
     * @var string
     */
    private $id;

    /**
     * Cron expression as array
     *
     * @var array
     */
    private $segments = [
        '*', // minute
        '*', // hour
        '*', // day (month)
        '*', // month
        '*' // day (week)
    ];

    /**
     * Pids for this event
     *
     * @var array
     */
    private $pids = [];

    /**
     * @var array
     */
    private $successCallbacks = [];

    /**
     * @var array
     */
    private $errorCallbacks = [];

    /**
     * @var \Origin\Process\BackgroundProcess
     */
    private $process;

    /**
     * Create an instance
     *
     * @param string $type
     * @param mixed $data
     * @param array $arguments
     */
    public function __construct(string $type, $data, array $arguments = [])
    {
        $this->type = $type;
        $this->data = $data;
        $this->arguments = $arguments;
    }

    /**
     * Schedule this event using a cron expression
     *
     * @param string $expression e.g. '0 0 * * 0;
     * @return \Origin\Schedule\Event
     */
    public function cron(string $expression): Event
    {
        $this->segments = explode(' ', $expression);
  
        if (count($this->segments) !== 5) {
            throw new InvalidArgumentException('Invalid cron expression');
        }

        return $this;
    }

    /**
     * @param integer $step set a value to run every x minutes
     * @return \Origin\Schedule\Event
     */
    private function minutes(int $step = null): Event
    {
        $this->segments = array_fill(0, 5, '*');

        if ($step) {
            $this->segments[0] = '*/' . $step;
        }
   
        return $this;
    }

    /**
     * Schedule this event to run every minute
     *
     * @return \Origin\Schedule\Event
     */
    public function everyMinute(): Event
    {
        return $this->minutes();
    }

    /**
     * Schedule this event to run every 5 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return \Origin\Schedule\Event
     */
    public function every5Minutes(): Event
    {
        return $this->minutes(5);
    }

    /**
     * Schedule this event to run every 10 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return \Origin\Schedule\Event
     */
    public function every10Minutes(): Event
    {
        return $this->minutes(10);
    }

    /**
     * Schedule this event to run every 15 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return \Origin\Schedule\Event
     */
    public function every15Minutes(): Event
    {
        return $this->minutes(15);
    }

    /**
     * Schedule this event to run every 20 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return \Origin\Schedule\Event
     */
    public function every20Minutes(): Event
    {
        return $this->minutes(20);
    }

    /**
    * Schedule this event to run every 30 minutes
    *
    * @param integer $step set a value to run every x minutes
    * @return \Origin\Schedule\Event
    */
    public function every30Minutes(): Event
    {
        return $this->minutes(30);
    }

    /**
     * Schedule this event to run hourly
     *
     * @return \Origin\Schedule\Event
     */
    public function hourly(): Event
    {
        $this->segments[0] = 0; // run at minute 0 of each hour

        return $this;
    }

    /**
     * Schedule this event to run daily
     *
     * @return \Origin\Schedule\Event
     */
    public function daily(): Event
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;

        return $this;
    }

    /**
     * Schedule this even to run weekly, on sunday
     *
     * @param integer $day
     * @return \Origin\Schedule\Event
     */
    public function weekly(): Event
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;
        $this->segments[4] = 0;

        return $this;
    }

    /**
     * Schedule this event to run monthly
     *
     * @return \Origin\Schedule\Event
     */
    public function monthly(): Event
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;
        $this->segments[2] = 1;

        return $this;
    }

    /**
     * Schedule this event to run quarter
     *
     * @return \Origin\Schedule\Event
     */
    public function quarterly(): Event
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;
        $this->segments[2] = 1;
        $this->segments[3] = '*/3';

        return $this;
    }

    /**
     * Schedule this event to run every year
     *
     * @return \Origin\Schedule\Event
     */
    public function yearly(): Event
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;
        $this->segments[2] = 1;
        $this->segments[3] = 1;

        return $this;
    }

    /**
     * Schedule this event to run on Sundays
     *
     * @return \Origin\Schedule\Event
     */
    public function sundays(): Event
    {
        $this->segments[4] = 0;

        return $this;
    }

    /**
     * Schedule this event to run on Mondays
     *
     * @return \Origin\Schedule\Event
     */
    public function mondays(): Event
    {
        $this->segments[4] = 1;

        return $this;
    }

    /**
     * Schedule this event to run on Tuesday
     *
     * @return \Origin\Schedule\Event
     */
    public function tuesdays(): Event
    {
        $this->segments[4] = 2;

        return $this;
    }

    /**
     * Schedule this event to run on Wednesday
     *
     * @return \Origin\Schedule\Event
     */
    public function wednesdays(): Event
    {
        $this->segments[4] = 3;

        return $this;
    }

    /**
     * Schedule this event to run on Thursday
     *
     * @return \Origin\Schedule\Event
     */
    public function thursdays(): Event
    {
        $this->segments[4] = 4;

        return $this;
    }

    /**
    * Schedule this event to run on Friday
    *
    * @return \Origin\Schedule\Event
    */
    public function fridays(): Event
    {
        $this->segments[4] = 5;

        return $this;
    }

    /**
    * Schedule this event to run on Saturday
    *
    * @return \Origin\Schedule\Event
    */
    public function saturdays(): Event
    {
        $this->segments[4] = 6;

        return $this;
    }

    /**
     * Schedule this event to run on particular day of the week
     *
     * @param integer $day 0= Sunday, 6 = Saturday
     * @return \Origin\Schedule\Event
     */
    public function on(int $day): Event
    {
        $this->segments[4] = $day;

        return $this;
    }
    
    /**
     * Schedules this event to run at a specific time
     *
     * @param integer $hours
     * @param integer $minutes
     * @return \Origin\Schedule\Event
     */
    public function at(int $hours, int $minutes = 0): Event
    {
        $this->segments[1] = $hours;
        $this->segments[0] = $minutes;

        return $this;
    }

    /**
     * Schedule this event to run on weekdays
     *
     * @return \Origin\Schedule\Event
     */
    public function weekdays(): Event
    {
        $this->segments[4] = '1-5';

        return $this;
    }

    /**
     * Schedule this event to run between two times e.g. 9-17
     *
     * @return \Origin\Schedule\Event
     */
    public function between(int $start, int $end): Event
    {
        $this->segments[1] = $start . '-' . $end;

        return $this;
    }

    /**
     * Starts an event in a background process
     *
     * @return boolean
     */
    public function start(): bool
    {
        if ($this->hasToManyProcesses()) {
            return false;
        }

        $this->process = new BackgroundProcess($this->getCommand(), ['escape' => false]);

        $this->process->start();
        
        $this->updateLockFile($this->process ->getPid());

        return true;
    }

    /**
     * Gets the Process for this event if any.
     *
     * @return BackgroundProcess|null
     */
    public function getProcess(): ? BackgroundProcess
    {
        return $this->process ?? null;
    }

    /**
     * Stops the event process, this is used to mark as complete or cancel the running
     * event
     *
     * @return void
     */
    public function stop(): void
    {
        if (! isset($this->process)) {
            throw new LogicException('Process was not started');
        }

        $error = false;
        // Cancel a running process
        if ($this->process->isRunning()) {
            $this->process->stop();
            $this->process->wait();
        }

        $this->executeCallbacks($this->afterCallbacks);

        if ($this->process->isSuccess()) {
            $this->executeCallbacks($this->successCallbacks);
        } else {
            $this->executeCallbacks($this->errorCallbacks);
        }
    }

    /**
     * Scheduler already checks, but this is complicated by running things in the background,
     * so this has been added here.
     * @return boolean
     */
    private function hasToManyProcesses(): bool
    {
        $loaded = count($this->pids());

        return $this->max > 0 && $loaded >= $this->max;
    }

    /**
     * Starts the execution process for the event
     *
     * @return bool false indicates an error
     */
    public function execute(): bool
    {
        if ($this->hasToManyProcesses()) {
            return false;
        }
        $this->executeCallbacks($this->beforeCallbacks);
 
        $result = false;
        switch ($this->type) {
            case 'command':
                $result = $this->dispatchCommand();
            break;
            case 'job':
                $result = $this->dispatchJob();
            break;
            case 'callable':
                $result = $this->dispatchCallable();
            break;
        }

        $this->executeCallbacks($this->afterCallbacks);
        
        if ($result) {
            $this->executeCallbacks($this->successCallbacks);
        } else {
            $this->executeCallbacks($this->errorCallbacks);
        }

        return $result;
    }

    /**
     * @return boolean
     */
    protected function meetsConditions(): bool
    {
        // work with when
        foreach ($this->filters as $callback) {
            if (! $callback()) {
                return false;
            }
        }

        // work with skip
        foreach ($this->rejects as $callback) {
            if ($callback()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $callbacks
     * @return void
     */
    private function executeCallbacks(array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Loads a list of PIDS for this event (updates the pid file)
     *
     * @return array
     */
    public function pids(): array
    {
        $lock = new Lock('event-' . $this->id());

        if (! $lock->acquire()) {
            throw new RuntimeException('Error getting lock');
        }

        // get valid PIDS
        $this->pids = $this->removeNonRunning($this->loadPids());

        // write all pids to file
        if ($this->pids) {
            if (file_put_contents($this->lockFile(), implode("\n", $this->pids) . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Error writing pids to file');
            }
        }

        $lock->release();

        return $this->pids;
    }

    /**
     * Checks if the pid is a value before converting to an int and then checks
     * if the pid is running
     *
     * @param array $pids
     * @return array
     */
    private function removeNonRunning(array $pids): array
    {
        $out = [];
        foreach ($pids as $pid) {
            if ($pid && posix_kill((int) $pid, 0)) {
                $out[] = $pid;
            }
        }

        return $out;
    }

    /**
     * Loads the pids from the file and removes when finished
     *
     * @return void
     */
    private function loadPids(): array
    {
        $out = [];
        $pidsFile = $this->lockFile();

        if (file_exists($pidsFile)) {
            $contents = file_get_contents($pidsFile);

            $out = explode("\n", trim($contents));
            unlink($pidsFile);
        }

        return $out;
    }

    /**
     * @return boolean
     */
    private function dispatchJob(): bool
    {
        $this->updateLockFile();

        return $this->data->dispatch(...$this->arguments);
    }

    /**
      * @return boolean
      */
    private function dispatchCallable(): bool
    {
        $this->updateLockFile();

        if ($this->output) {
            ob_start();
        }

        $result = call_user_func_array($this->data, $this->arguments) !== false;

        if ($this->output) {
            $output = ob_get_clean();
            file_put_contents($this->output, $output, $this->appendOutput ? FILE_APPEND : 0);
        }

        return $result;
    }

    /**
      * @return boolean
      */
    private function dispatchCommand(): bool
    {
        $this->process = new BackgroundProcess($this->getCommand(), ['escape' => false]);

        $this->process->start();
        
        $this->updateLockFile($this->process->getPid());
    
        /**
         * Originally was running into issues with CPU usage of 2% for each background process, even if the
         * scripts did nothing. This no longer occurs. I think happened because of the usleep nuber combined with
         * runing PHP within PHP.
         */
        $this->process->wait();

        return $this->process->isSuccess();
    }

    /**
     * Gets the command
     *
     * @return string|null
     */
    private function getCommand(): ?string
    {
        $command = $this->data;

        if ($this->output) {
            $redirect = $this->appendOutput ? '>>' : '>';
            $command .= " {$redirect} {$this->output} 2>&1";
        }

        return $command;
    }

    /**
     * Adds the pid to the pidsFile
     *
     * @param integer $pid
     * @return boolean
     */
    private function updateLockFile(int $pid = null): bool
    {
        $pid = $pid ?: getmypid();

        $this->pids[] = $pid;

        return (bool) file_put_contents(
            $this->lockFile(),
            $pid . PHP_EOL,
            LOCK_EX | FILE_APPEND
        );
    }
    
    /**
     * Gets the lock file for this event
     *
     * @return string
     */
    private function lockFile(): string
    {
        return sys_get_temp_dir() . '/event-' . $this->id . '.pids';
    }

    /**
     * Sends the output of a command to a file
     *
     * @param string $path
     * @param boolean $append
     * @return \Origin\Schedule\Event
     */
    public function output(string $path, bool $append = false): Event
    {
        $this->output = $path;
        $this->appendOutput = $append;

        return $this;
    }

    /**
     * Runs the command in the background
     *
     * @return \Origin\Schedule\Event
     */
    public function background(): Event
    {
        $this->background = true;

        return $this;
    }

    /**
     * Runs this closure before the event is executed
     *
     * @param \Closure $closure
     * @return \Origin\Schedule\Event
     */
    public function before(Closure $closure): Event
    {
        $this->beforeCallbacks[] = $closure;

        return $this;
    }

    /**
     * Runs this closure after the event is executed
     *
     * @param \Closure $closure
     * @return \Origin\Schedule\Event
     */
    public function after(Closure $closure): Event
    {
        $this->afterCallbacks[] = $closure;

        return $this;
    }

    /**
     * Runs this closure if the the task was run without errors
     *
     * @param \Closure $closure
     * @return \Origin\Schedule\Event
     */
    public function onSuccess(Closure $closure): Event
    {
        $this->successCallbacks[] = $closure;

        return $this;
    }

    /**
     * Runs this closure if the task returned an error during the dispatch process
     *
     * @param \Closure $closure
     * @return \Origin\Schedule\Event
     */
    public function onError(Closure $closure): Event
    {
        $this->errorCallbacks[] = $closure;

        return $this;
    }

    /**
     * Set the event so that the task will run even if maintence mode is enabled
     *
     * @return \Origin\Schedule\Event
     */
    public function evenInMaintenanceMode(): Event
    {
        $this->runInMaintenanceMode = true;

        return $this;
    }
    /**
     * Checks if event is due to be run
     *
     * @return boolean
     */
    public function isDue(string $time = 'now'): bool
    {
        if (! (new CronExpression($this->expression(), $time))->isDue()) {
            return false;
        }

        if ($this->maintenanceModeEnabled() && ! $this->runInMaintenanceMode) {
            return false;
        }
    
        return $this->meetsConditions();
    }

    /**
     * Limits the number of concurrent processes of the command that can be run
     *
     * @return \Origin\Schedule\Event
     */
    public function limit(int $processes): Event
    {
        $this->max = $processes;

        return $this;
    }

    /**
     * The number of times this task will be spawned
     *
     * @return \Origin\Schedule\Event
     */
    public function processes(int $count): Event
    {
        $this->processes = $count;

        return $this;
    }

    /**
     * Apply a condition so that the task is only run if the closure returns true or the boolean is true.
     *
     * @param Callable|bool
     * @return \Origin\Schedule\Event
     */
    public function when($callback): Event
    {
        $this->filters[] = is_callable($callback) ? $callback : function () use ($callback) {
            return $callback;
        };

        return $this;
    }

    /**
     * Apply a condition so that the task is skipped if the closure returns true or the boolean is true.
     *
     * @param Callable|bool
     * @return \Origin\Schedule\Event
     */
    public function skip($callback): Event
    {
        $this->rejects[] = is_callable($callback) ? $callback : function () use ($callback) {
            return $callback;
        };

        return $this;
    }

    /**
     * Gets the event id. Note that the length of id might change in the future if problems are
     * experienced.
     *
     * @return string
     */
    public function id(): string
    {
        if (! isset($this->id)) {
            $this->id = $this->generateId();
        }

        return $this->id;
    }

    /**
     * Creates the ID by serializing the Closure or object and then by
     * hashing it
     *
     * @return string
     */
    private function generateId(): string
    {
        $data = $this->data;
        if ($this->data instanceof Closure) {
            $data = $this->serializeClosure($this->data);
        } elseif (is_object($this->data)) {
            $data = serialize($this->data);
        }

        return $this->hash(json_encode([
            $this->type, $data, $this->arguments, $this->expression()
        ]));
    }

    /**
     * Gets the generated config for this event
     *
     * @return array
     */
    public function config(): array
    {
        return [
            'id' => $this->id(),
            'type' => $this->type,
            'expression' => $this->expression(),
            'background' => $this->background,
            'maintenanceMode' => $this->runInMaintenanceMode,
            'processes' => $this->processes,
            'max' => $this->max
        ];
    }

    /**
     * @param Closure $closure
     * @return string
     */
    private function serializeClosure(Closure $closure): string
    {
        $function = new ReflectionFunction($closure);
        $file = new SplFileObject($function->getFileName());
        $file->seek($function->getStartLine() - 1);

        $out = '';
        while ($file->key() < $function->getEndLine()) {
            $out .= $file->current();
            $file->next();
        }

        return trim($out);
    }

    /**
     * Hash function for the id
     *
     * @param string $data
     * @return string
     */
    private function hash(string $data): string
    {
        return substr(md5($data), 0, 12);
    }

    /**
     * Gets the cron expression
     *
     * @return string
     */
    public function expression(): string
    {
        return implode(' ', $this->segments);
    }

    /**
    * Check if app is in maintencemode
    *
    * @return boolean
    */
    private function maintenanceModeEnabled(): bool
    {
        return file_exists(tmp_path('maintenance.json'));
    }
}
