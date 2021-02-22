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
use ReflectionFunction;
use InvalidArgumentException;
use Origin\Process\BackgroundProcess;

class Event
{
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
     * Path to output file
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
     * Don't run command in background process
     *
     * @var boolean
     */
    private $runInForeground = false;

    /**
     * This event should run even if in maintence mode
     *
     * @var boolean
     */
    private $ignoreMaintenanceMode = false;

    /**
     * Restricts the maximum number of instances of this tasks running at the same time
     *
     * @var int
     */
    private $max = 0;

    /**
     * How many times this task will be executed
     *
     * @var integer
     */
    private $count = 1;

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
     * @param string $type
     * @param string|job|callable|object $data
     * @param array $arguments
     */
    public function __construct(string $type, $data, array $arguments = [])
    {
        $this->type = $type;
        $this->data = $data;
        $this->arguments = $arguments;

        $this->id = $this->id();
    }

    /**
     * Schedule this event using a cron expression
     *
     * @param string $expression e.g. '0 0 * * 0;
     * @return self
     */
    public function cron(string $expression)
    {
        $this->segments = explode(' ', $expression);
  
        if (count($this->segments) !== 5) {
            throw new InvalidArgumentException('Invalid cron expression');
        }

        return $this;
    }

    /**
     * @param integer $step set a value to run every x minutes
     * @return self
     */
    private function minutes(int $step = null)
    {
        $this->segments = array_fill(0, 5, '*');

        if ($step) {
            $this->segments[0] = '*/' . $step;
        }
   
        return $this;
    }

    /**
     * Schedule this event to run every minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return self
     */
    public function everyMinute()
    {
        return $this->minutes();
    }

    /**
     * Schedule this event to run every 5 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return self
     */
    public function every5Minutes()
    {
        return $this->minutes(5);
    }

    /**
     * Schedule this event to run every 10 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return self
     */
    public function every10Minutes()
    {
        return $this->minutes(10);
    }

    /**
     * Schedule this event to run every 15 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return self
     */
    public function every15Minutes()
    {
        return $this->minutes(15);
    }

    /**
     * Schedule this event to run every 20 minutes
     *
     * @param integer $step set a value to run every x minutes
     * @return self
     */
    public function every20Minutes()
    {
        return $this->minutes(20);
    }

    /**
    * Schedule this event to run every 30 minutes
    *
    * @param integer $step set a value to run every x minutes
    * @return self
    */
    public function every30Minutes()
    {
        return $this->minutes(30);
    }

    /**
     * Schedule this event to run hourly
     *
     * @return self
     */
    public function hourly()
    {
        $this->segments[0] = 0; // run at minute 0 of each hour

        return $this;
    }

    /**
     * Schedule this event to run daily
     *
     * @return self
     */
    public function daily()
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;

        return $this;
    }

    /**
     * Schedule this even to run weekly, on sunday
     *
     * @param integer $day
     * @return self
     */
    public function weekly()
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;
        $this->segments[4] = 0;

        return $this;
    }

    /**
     * Schedule this event to run monthly
     *
     * @return self
     */
    public function monthly()
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;
        $this->segments[2] = 1;

        return $this;
    }

    /**
     * Run tasks every quarter
     *
     * @return self
     */
    public function quarterly()
    {
        $this->segments[0] = 0;
        $this->segments[1] = 0;
        $this->segments[2] = 1;
        $this->segments[3] = '*/3';

        return $this;
    }

    /**
     * Run tasks every year
     *
     * @return self
     */
    public function yearly()
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
     * @return self
     */
    public function sundays()
    {
        $this->segments[4] = 0;

        return $this;
    }

    /**
     * Schedule this event to run on Mondays
     *
     * @return self
     */
    public function mondays()
    {
        $this->segments[4] = 1;

        return $this;
    }

    /**
     * Schedule this event to run on Tuesday
     *
     * @return self
     */
    public function tuesdays()
    {
        $this->segments[4] = 2;

        return $this;
    }

    /**
     * Schedule this event to run on Wednesday
     *
     * @return self
     */
    public function wednesdays()
    {
        $this->segments[4] = 3;

        return $this;
    }

    /**
     * Schedule this event to run on Thursday
     *
     * @return self
     */
    public function thursdays()
    {
        $this->segments[4] = 4;

        return $this;
    }

    /**
    * Schedule this event to run on Friday
    *
    * @return self
    */
    public function fridays()
    {
        $this->segments[4] = 5;

        return $this;
    }

    /**
    * Schedule this event to run on Saturday
    *
    * @return self
    */
    public function saturdays()
    {
        $this->segments[4] = 6;

        return $this;
    }

    /**
     * Schedule this event to run on particular day of the week
     *
     * @param integer $day 0= Sunday, 6 = Saturday
     * @return self
     */
    public function on(int $day)
    {
        $this->segments[4] = $day;

        return $this;
    }
    
    /**
     * Schedules the event to run at a specific time
     *
     * @param integer $hours
     * @param integer $minutes
     * @return self
     */
    public function at(int $hours, int $minutes = 0)
    {
        $this->segments[1] = $hours;
        $this->segments[0] = $minutes;

        return $this;
    }

    /**
     * Run tasks on weekdays
     *
     * @return self
     */
    public function weekdays()
    {
        $this->segments[4] = '1-5';

        return $this;
    }

    /**
     * Run tasks between two different hours, e.g. 9-17
     *
     * @return self
     */
    public function between(int $start, int $end)
    {
        $this->segments[1] = $start . '-' . $end;

        return $this;
    }

    /**
     * Executes the event
     *
     * @return void
     */
    public function execute(): void
    {
        $this->loadLockFile();

        // work with when
        foreach ($this->filters as $callback) {
            if ($callback() !== true) {
                return;
            }
        }

        // work with skip
        foreach ($this->rejects as $callback) {
            if ($callback() === true) {
                return;
            }
        }
        
        $this->executeCallbacks($this->beforeCallbacks);
       
        for ($i = 0;$i < $this->count;$i++) {
            $this->spawnProcess();
        }

        $this->executeCallbacks($this->afterCallbacks);
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
     * Runs the task x amount of times
     *
     * @return void
     */
    private function spawnProcess(): void
    {
        if ($this->max > 0 && count($this->pids) >= $this->max) {
            return;
        }
        
        switch ($this->type) {
            case 'job':
                $this->dispatchJob();
            break;
            case 'command':
                $this->dispatchCommand();
            break;
            case 'callable':
                $this->dispatchCallable();
            break;
        }
    }

    /**
     * Loads the lock file, checks if the pids are running
     */
    private function loadLockFile(): void
    {
        $this->pids = [];

        $lockfile = $this->lockFile();
        if (! file_exists($lockfile)) {
            return ;
        }
        $data = json_decode(file_get_contents($lockfile), true);

        foreach ($data as $index => $pid) {
            if (! posix_kill(intval($pid), 0)) {
                unset($data[$index]);
            }
        }

        $this->pids = array_values($data); // reindex always
    }

    /**
     * @return void
     */
    private function dispatchJob(): void
    {
        $this->updateLockFile();
        $this->data->dispatch(...$this->arguments);
    }

    /**
     * @return void
     */
    private function dispatchCallable(): void
    {
        $this->updateLockFile();
        call_user_func_array($this->data, $this->arguments);
    }

    /**
     * @return void
     */
    private function dispatchCommand(): void
    {
        $command = $this->data;

        if ($this->output) {
            $redirect = $this->appendOutput ? '>>' : '>';
            $command .= " {$redirect} {$this->output} 2>&1";
        }
     
        $process = new BackgroundProcess($command, ['escape' => false]);
       
        $process->start();
        
        $this->updateLockFile($process->pid());
        
        if ($this->runInForeground) {
            $process->wait();
        }
    }

    /**
     * Adds the pid to the lockfile
     *
     * @param integer $pid
     * @return boolean
     */
    private function updateLockFile(int $pid = null): bool
    {
        $this->pids[] = $pid ?: getmypid();

        return (bool) file_put_contents(
            $this->lockFile(), json_encode($this->pids), LOCK_EX
        );
    }
    
    /**
     * Gets the lock file for this event
     *
     * @return string
     */
    private function lockFile(): string
    {
        return sys_get_temp_dir() . '/origin-' . $this->id . '.lock';
    }

    /**
     * Sends the output of a command to a file
     *
     * @param string $path
     * @param boolean $append
     * @return self
     */
    public function output(string $path, bool $append = false)
    {
        $this->output = $path;
        $this->appendOutput = $append;

        return $this;
    }

    /**
     * Waits for the command to finish
     *
     * @return self
     */
    public function wait()
    {
        $this->runInForeground = true;

        return $this;
    }

    /**
     * Runs this callable before the event is executed
     *
     * @param callable $callable
     * @return void
     */
    public function before(callable $callable)
    {
        $this->beforeCallable = $callable;
    }

    /**
     * Runs this callable after the event is executed
     *
     * @param callable $callable
     * @return void
     */
    public function after(callable $callable)
    {
        $this->afterCallable = $callable;
    }

    /**
     * Set the event so that the task will run even if maintence mode is enabled
     *
     * @return self
     */
    public function inMaintenanceMode()
    {
        $this->ignoreMaintenanceMode = true;

        return $this;
    }

    /**
     * Checks if this even should be run in maintence mode, which is
     * set by runInMaintenanceMode
     *
     * @return boolean
     */
    public function runsInMaintenanceMode(): bool
    {
        return $this->ignoreMaintenanceMode;
    }

    /**
     * Checks if event is due to be run
     *
     * @return boolean
     */
    public function isDue(string $time = 'now'): bool
    {
        return (new CronExpression($this->expression(), $time))->isDue();
    }

    /**
     * Limits the number of concurrent instances of the command that can be run
     *
     * @return self
     */
    public function limit(int $instances)
    {
        $this->max = $instances;

        return $this;
    }

    /**
     * The number of times this task will be executed
     *
     * @return self
     */
    public function count(int $count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Apply a condition so that the task is only run if the closure returns true or the boolean is true.
     *
     * @param Callable|bool
     * @return self
     */
    public function when($callback)
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
     * @return self
     */
    public function skip($callback)
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
        $data = $this->data;
        if ($this->data instanceof Closure) {
            $data = $this->serializeClosure($this->data);
        }

        return $this->hash(json_encode([$this->type,$data,$this->arguments]));
    }

    /**
     * @param Closure $closure
     * @return string
     */
    private function serializeClosure(Closure $closure): string
    {
        $out = '';

        $function = new ReflectionFunction($closure);
        $file = new SplFileObject($function->getFileName());
        $file->seek($function->getStartLine() - 1);
        
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
}
