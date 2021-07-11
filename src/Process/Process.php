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

/**
 * Process
 *
 * @internal refactored to go through background process due to issue with getting output, cant use stream
 * so you have to go through a loop, essentially recreating code in the background process
 */
class Process extends BaseProcess
{
    /**
     * @var string
     */
    protected $stdout = '';

    /**
     * @var string
     */
    protected $stderr = '';

    /**
     * @var integer|null
     */
    protected $exitCode = null;

    /**
     * Raw command
     *
     * @var string|array
     */
    protected $command;
    protected $options;

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
        $this->command = $stringOrArray;
        $this->options = $options;
    }

    /**
     * Executes a command
     *
     * @return boolean
     */
    public function execute(): bool
    {
        $this->stdout = $this->stderr = '';
        
        $process = new BackgroundProcess($this->command, $this->options);
        $process->start();
        $process->wait();

        $this->stdout = $process->getOutput();
        $this->stderr = $process->getErrorOutput();

        $this->exitCode = $process->exitCode();

        return $this->exitCode === 0;
    }
    
    /**
     * Gets the exit code or null if it was not run
     *
     * @return integer|null
     */
    public function getExitCode(): ? int
    {
        return $this->exitCode;
    }

    /**
     * Gets the output from stdout
     *
     * @return string
     */
    public function getOutput(): string
    {
        return $this->stdout;
    }

    /**
     * Gets the error output from stderr
     *
     * @return string|null
     */
    public function getErrorOutput(): ? string
    {
        return $this->stderr;
    }

    /**
       * @deprecated 3.26.0
       * @codeCoverageIgnore
       * @return string
       */
    public function output(): string
    {
        // does not require core/functions
        trigger_error('Process::output has been deprecated use Process::getOutput instead', E_USER_DEPRECATED);
    
        return $this->getOutput();
    }
    
    /**
     * @deprecated 3.26.0
     * @codeCoverageIgnore
     * @return string
     */
    public function error(): string
    {
        trigger_error('Process::error has been deprecated use Process::getErrorOutput instead', E_USER_DEPRECATED);

        return $this->getErrorOutput();
    }
    
    /**
    * @deprecated 3.26.0
    * @codeCoverageIgnore
    * @return integer|null
    */
    public function exitCode(): ?int
    {
        trigger_error('Process::exitCode has been deprecated use Process::getExitCode instead', E_USER_DEPRECATED);

        return $this->getExitCode();
    }
}
