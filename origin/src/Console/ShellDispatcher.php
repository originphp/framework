<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Console;

use Origin\Console\ConsoleOutput;
use Origin\Core\Inflector;
use Origin\Console\Exception\MissingShellException;
use Origin\Console\Exception\MissingShellMethodException;
use Origin\Core\Resolver;

class ShellDispatcher
{
    /**
     * ConsoleOutput object
     *
     * @var ConsoleOutput
     */
    protected $consoleOutput = null;

    /**
     * Holds the arguments called
     *
     * @var array
     */
    protected $args = [];

    protected $shell = null;

    public function __construct(array $arguments =[])
    {
        $this->consoleOutput = new ConsoleOutput();
        $this->args = array_slice($arguments, 1);
    }

    public function stdout(string $data)
    {
        $this->consoleOutput->write($data);
    }

    /**
     * Starts the dispatcher process
     *
     * @return void
     */
    public function start()
    {
        $this->stdout("\033[1;32m");
        $this->stdout("\033[2J\033[;H"); // clear screen
        $this->stdout("<blue>OriginPHP Shell v1.0</blue>\n\n");
        $this->stdout("\033[37m"); // Set all text to white

        $shell = array_shift($this->args);
        if ($shell == false) {
            $this->stdout("Usage: console <yellow>shell</yellow>\n");
            $this->stdout("       console <yellow>shell command</yellow>\n");
            $this->stdout("\033[0m\n"); // Reset
            return false;
        }

        $object = $this->dispatch($shell);

        $this->stdout("\033[0m\n"); // Reset
    }

    /**
     * Dispatches the request
     *
     * @param string $shell
     * @return void
     */
    protected function dispatch(string $shell)
    {
        list($plugin, $class) = pluginSplit($shell);

        $base = 'App\Console\\';
        if ($plugin) {
            $base = Inflector::camelize($plugin) .'\Console\\';
        }
        $class = Inflector::camelize($class) . 'Shell';
        if (!class_exists($base . $class)) {
            $base = 'Origin\Console\\';
            if (!class_exists($base . $class)) {
                throw new MissingShellException($class);
            }
        }

        $className = $base . $class;
    
        $method = 'main';
        if ($this->args) {
            $method = array_shift($this->args);
        }
        
        $object = $this->buildShell($className, $method);

        return $this->invoke($object, $method);
    }
    /**
     * Create the ShellObject and check that method exists and is not private
     * or protected
     *
     * @param string $class
     * @param string $method
     * @return void
     */
    protected function buildShell(string $class, string $method)
    {
        $object = new $class($this->args, $this->consoleOutput);
        if (!method_exists($object, $method)) {
            throw new MissingShellMethodException([$shell,$method]);
        }
        if (!$object->isAccessible($method)) {
            throw new MissingShellMethodException([$shell,$method]);
        }
        return $object;
    }
    
    /**
     * Invokes the shell method and starts the startup and shutdown processes which
     * trigger callbacks
     *
     * @param Shell $shell
     * @param string $method
     * @return void
     */
    protected function invoke(Shell $shell, string $method)
    {
        $shell->startupProcess();
        $shell->{$method}();
        $shell->shutdownProcess();
        return $shell;
    }
}
