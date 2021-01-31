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
     * @var integer|null
     */
    protected $timeout = null;

    /**
     * @param string|array $stringOrArray
     * @param array $options The following options are supported
     *  - directory: the directory to execute the command in, default is getcwd
     *  - env: an array of key values for environment variables
     *  - output: (bool) default if TTY is supported output will be sent to screen
     *  - escape: default: true escapes the command
     * @return void
     */
    public function __construct($stringOrArray, array $options = [])
    {
        $options += [
            'directory' => getcwd(),
            'env' => [],
            'output' => false,
            'escape' => true
        ];

        $this->setDirectory($options['directory']);
        $this->setEnv((array) $options['env']);
        $this->setCommand($stringOrArray, $options['escape']);
        
        $this->outputEnabled = $options['output'];
    }

    /**
     * Executes a command
     *
     * @param string|array $stringOrArray
     * @param array $options The following options are supported
     *  - directory: the directory to execute the command in, default is getcwd
     *  - env: an array of key values for environment variables
     *  - output: (bool) default if TTY is supported output will be sent to screen
     *  - escape: default: true escapes the command
     * @return boolean
     */
    public function execute(): bool
    {
        $this->stdout = $this->stderr = '';
        $this->exitCode = null;

        $process = proc_open(
            $this->command, $this->descriptorspec($this->outputEnabled), $pipes, $this->directory, $this->env
        );
        if (! $process) {
            return false;
        }
        
        $this->stdout = stream_get_contents($pipes[1]);
        $this->stderr = stream_get_contents($pipes[2]);
       
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
       
        $this->exitCode = proc_close($process);

        return $this->exitCode === 0;
    }

    /**
     * Gets the exit code or null if it was not run
     *
     * @return integer|null
     */
    public function exitCode(): ? int
    {
        return $this->exitCode;
    }

    /**
     * Gets the output from stdout
     *
     * @return string
     */
    public function output(): string
    {
        return $this->stdout;
    }

    /**
     * Gets the error output from stderr
     *
     * @return string|null
     */
    public function error(): ? string
    {
        return $this->stderr;
    }
}
