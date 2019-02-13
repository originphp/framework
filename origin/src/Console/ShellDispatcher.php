<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
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

use Origin\Console\ConsoleInput;
use Origin\Console\ConsoleOutput;
use Origin\Console\Exception\MissingShellException;
use Origin\Console\Exception\MissingShellMethodException;

use Origin\Core\Configure;
use Origin\Core\Inflector;
use Origin\Core\Plugin;
use Origin\Core\Resolver;

class ShellDispatcher
{
    /**
     * ConsoleOutput object
     *
     * @var ConsoleOutput
     */
    protected $output = null;

    /**
     * Holds the arguments called
     *
     * @var array
     */
    protected $args = [];

    protected $shell = null;

    public function __construct(array $arguments = [], ConsoleOutput $consoleOutput)
    {
        $this->args = array_slice($arguments, 1);
        $this->output = $consoleOutput;
    }

    /**
     * Outputs string to console
     *
     * @param string $data
     * @return void
     */
    public function out(string $data)
    {
        $this->output->write($data);
    }

    /**
     * Starts the dispatcher process
     * @return void
     */
    public function start()
    {
        $this->out("\033[2J\033[;H"); // clear screen
        $this->out("<blue>OriginPHP Console v1.0</blue>\n\n");

        $shell = array_shift($this->args);
        if ($shell) {
            return $this->dispatch($shell);
        }

        $this->showUsage();
        return false;
    }

    protected function showUsage()
    {
        $this->out("Usage: console <yellow>shell</yellow>\n");
        $this->out("       console <yellow>shell command</yellow>\n");
        $this->out("\033[0m\n"); // Reset
        $shells = $this->getShellList();
        if ($shells) {
            $this->out("Available Shells:\n");
            foreach ($shells as $namespace => $commands) {
                if ($commands) {
                    $this->out("\n<white>{$namespace}</white>\n");
                    foreach ($commands as $command) {
                        $this->out("<cyan>{$command}</cyan>\n");
                    }
                }
            }
            $this->out("\n");
        }
    }

    /**
     * Dispatches the request base on the shell name
     *
     * @param string $shell shell_name or Plugin.shell_name
     * @return void
     */
    protected function dispatch(string $shell)
    {
        list($plugin, $class) = pluginSplit($shell);
        $base = '';

        if ($plugin === null) {
            $shells = $this->getShellList();
         
            if (in_array($shell, $shells['App'])) {
                $base = Configure::read('App.namespace') .'\Console\\';
            } elseif (in_array($shell, $shells['Core'])) {
                $base = 'Origin\Console\\';
            } else {
                
                // Search Plugins
                foreach ($shells as $plugin => $commands) {
                    if ($plugin === 'App' or $plugin ==='Core') {
                        continue;
                    }
                    if (in_array($shell, $commands)) {
                        $base = $plugin . '\Console\\';
                        break;
                    }
                }
            }
        } else {
            $base = Inflector::camelize($plugin) .'\Console\\';
        }

        $class = Inflector::camelize($class) . 'Shell';
 
        if (!class_exists($base . $class)) {
            throw new MissingShellException($base . $class);
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
        $shell = new $class($this->args, $this->output, new ConsoleInput());
        if (!method_exists($shell, $method)) {
            throw new MissingShellMethodException([$class,$method]);
        }
        if (!$shell->isAccessible($method)) {
            throw new MissingShellMethodException([$class,$method]);
        }
        return $shell;
    }

    /**
     * Gets a list of available shells
     *
     * @return void
     */
    protected function getShellList()
    {
        $shells = [];
        $shells['App'] = $this->scandir(SRC . DS . 'Console');
        $shells['Core'] = $this->scandir(ORIGIN . DS. 'src' . DS . 'Console');

        $plugins = Plugin::loaded();
        sort($plugins);
        foreach ($plugins as $plugin) {
            $shells[$plugin] = $this->scandir(ROOT . DS . 'plugins' . DS . Inflector::underscore($plugin) . DS . 'src' . DS . 'Console');
        }
        
        return $shells;
    }

    /**
     * Scans a directory for Shellsxx
     *
     * @param string $folder
     * @return void
     */
    protected function scandir(string $folder)
    {
        $ignore = ['Shell.php','AppShell.php'];
        $result = [];
     
        if (file_exists($folder)) {
            $files = scandir($folder);
            foreach ($files as $file) {
                if (substr($file, -9) === 'Shell.php' and !in_array($file, $ignore)) {
                    $result[] = Inflector::underscore(substr($file, 0, -9));
                }
            }
        }
        
        return $result;
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
