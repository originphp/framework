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
namespace Origin\Process;

use LogicException;
use RuntimeException;
use Origin\Process\Exception\TimeoutException;

class BackgroundProcess extends BaseProcess
{
    /**
     * @var resource|null
     */
    protected $process = null;

    /**
     * @var array
     */
    protected $pipes = [];

    /**
     * The time that the process was started
     * 
     * @var integer|null
     */
    protected $started = null;

    /**
     * @var string
     */
    protected $stdout = '';

    /**
     * @var string
     */
    protected $stderr = '';

    /**
     * Proc status result
     *
     * @var array
     */
    private $status = [];

    /**
     * @param string|array $stringOrArray
     * @param array $options The following options are supported
     *  - directory: the directory to execute the command in, default is getcwd
     *  - env: an array of key values for environment variables
     *  - output: (bool) default if TTY is supported output will be sent to screen
     *  - escape: default: true escapes the command
     *  - timeout: set the timeout value in seconds
     * @return void
     */
    public function __construct($stringOrArray, array $options = [])
    {
        $options += [
            'directory' => getcwd(),
            'env' => [],
            'timeout' => null,
            'escape' => true,
            'output' => false
        ];

        $this->setDirectory($options['directory']);
        $this->setEnv((array) $options['env']);
        $this->setCommand($stringOrArray, $options['escape']);

        $this->timeout = $options['timeout'];
        $this->outputEnabled = $options['output'];

    }

    /**
     * Starts the background process
     *
     * @return void
     */
    public function start()
    {

        if ($this->process && $this->isRunning()) {
            throw new RuntimeException('This process is already running another background process');
        }

        $this->process = proc_open(
            $this->command, $this->descriptorspec(), $this->pipes, $this->directory, $this->env
        );

        if (! is_resource($this->process)) {
            return throw new RuntimeException('Error starting process');
        }

        $this->started = time();
  
        if(!$this->outputEnabled){
            stream_set_blocking($this->pipes[0], false);
            stream_set_blocking($this->pipes[1], false);
            stream_set_blocking($this->pipes[2], false);
        }
    
    }

    /**
     * Checks if the process has timedout
     *
     * @return boolean
     */
    public function hasTimedOut(): bool
    {
        return $this->timeout && $this->isRunning() && time() > $this->started;
    }

    /**
     * Checks this background process is running
     *
     * @return boolean
     */
    public function isRunning(): bool
    {
        $this->updateStatus();
        return $this->status('running') ?: false;
    }

    /**
     * Waits for the background process to stop running
     *
     * @return int|null
     */
    public function wait()
    {
        $this->checkStarted();

        while ($this->isRunning()) {
            $this->checkTimeout();

            usleep(1000);
        }

        return $this->exitCode();
    }

    /**
     * Waits until a condition is met
     *
     * @example
     * function ($output,$error) {
     *   return str_contains($output,$ready);
     * }
     * @param callable $callback
     * @return bool
     */
    public function waitUntil(callable $callback): bool
    {
        $this->checkStarted();

        while (true) {
            if (! $this->isRunning()) {
                return false;
            }
            if ($callback($this->output(), $this->error()) === true) {
                return true;
            }

            $this->checkTimeout();

            usleep(1000);
        }
    }

    /**
     * @return void
     */
    private function checkTimeout() : void 
    {
        if($this->hasTimedOut()){
            $this->stop();
            throw new TimeoutException(sprintf('Maximum timeout of %s reached', $this->timeout));
        }
    }

    /**
     * @return void
     */
    private function checkStarted() : void
    {
        if(!is_resource($this->process)){
            throw new LogicException('The process must be started');
        }
    }

    /**
     * Stops the process
     *
     * @return boolean
     */
    public function stop(): bool
    {
        $this->checkStarted();

        if ($this->isRunning() && extension_loaded('posix')) {
            $pid = proc_get_status($this->process)['pid'];
            return posix_kill($pid, SIGKILL);
        }

        return false;
    }

    /**
     * Gets the full output for this process so far from stdout
     *
     * @return string
     */
    public function output(): string
    {
        $this->readOutput();
        return $this->stdout;
    }

    /**
    * Gets the full output for this process so far from stderr
     *
     * @return string
     */
    public function error():  string
    {
        $this->readError();
        return $this->stderr;
    }

    /**
     * Gets any new output since the last the call 
     * 
     * @return string
     */
    public function readOutput() : ?string 
    {
        if(isset($this->pipes[1])){
            $this->stdout .= stream_get_contents($this->pipes[1]);
        }
        return null;
    }

    /**
     * Gets any new error output since the last the call 
     * 
     * @return string
     */
    public function readError() : ?string 
    {
        if(isset($this->pipes[2])){
            return $this->stderr .= stream_get_contents($this->pipes[2]);
        }
        return null;
    }

    /**
     * @return integer|null
     */
    public function exitCode(): ?int
    {  
        return $this->status('exitcode');
    }

    /**
     * @return void
     */
    private function updateStatus() : void 
    {
        if (!is_resource($this->process)) {
            return;
        }
        $this->status = proc_get_status($this->process);

        if($this->status['running'] === false){

            // get last input
            $this->readOutput();
            $this->readError();

            // close
            if(!$this->outputEnabled){
                fclose($this->pipes[0]);
                fclose($this->pipes[1]);
                fclose($this->pipes[2]);
            }
           
            $this->process = null;
            $this->pipes = [];
            $this->started = null;
        }
    }

    /**
     * Gets the status of the process 
     * @param string $key
     * @return mixed
     */
    private function status(string $key)
    {
        $this->updateStatus();

        return $this->status[$key] ?? null;
    }
}
