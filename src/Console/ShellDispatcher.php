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
use Origin\Console\Exception\StopExecutionException;

use Origin\Core\Configure;
use Origin\Core\Inflector;
use Origin\Core\Plugin;
use Origin\Core\Resolver;

class ShellDispatcher
{
    /**
     * ConsoleOutput object
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $output = null;

    /**
     * ConsoleOutput object
     *
     * @var \Origin\Console\ConsoleInput
     */
    protected $input = null;

    /**
     * Holds the arguments called
     *
     * @var array
     */
    protected $args = [];


    /**
     * Shell
     *
     * @var \Origin\Console\Shell
     */
    protected $shell = null;

    /**
     * Wether or not an error occured
     *
     * @var boolean
     */
    protected $errors = false;
    public function error(bool $result = null)
    {
        if ($result !== null) {
            $this->error = $result;
        }
        return $this->error;
    }

    public function __construct(array $arguments = [], ConsoleOutput $consoleOutput, ConsoleInput $consoleInput)
    {
        $this->args = array_slice($arguments, 1);
        $this->output = $consoleOutput;
        $this->input = $consoleInput;
    }

    /**
     * Outputs string to console
     *
     * @param string $data
     * @return void
     */
    public function out(string $data,$newLine = true)
    {
        $this->output->write($data,true);
    }

    /**
     * Starts the dispatcher process
     * @return void
     */
    public function start()
    {

        $shell = array_shift($this->args);
        if ($shell) {
            return $this->dispatch($shell);
        }

        $this->showUsage();
        return false;
    }

    protected function showUsage()
    {
        $this->out("<text>OriginPHP</text>");
        $this->out("");
        $this->out("<heading>Usage:</heading>");
        $this->out("  <text>console shell</text>");
        $this->out("  <text>console shell command</text>");
        $this->out("\033[0m\n"); // Reset
        $shells = $this->getShellList();
        if ($shells) {
            $this->out("<heading>Available Shells:</heading>");
            foreach ($shells as $namespace => $commands) {
                if ($commands) {
                    $this->out("\n<text>{$namespace}</text>");
                    foreach ($commands as $command) {
                        $this->out("  <info>{$command}</info>");
                    }
                }
            }
            $this->out("");
        }
    }

    protected function getClass(string $shell)
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

        return $base . Inflector::camelize($class) . 'Shell';
    }

    /**
     * Dispatches the request base on the shell name
     *
     * @param string $shell shell_name or Plugin.shell_name
     * @return bool
     */
    protected function dispatch(string $shell)
    {
        $className = $this->getClass($shell);
        if (!class_exists($className)) {
            throw new MissingShellException($className);
        }
        $this->shell = new $className($this->output, $this->input);
        $description = $this->shell->description;
        if(!$description){
            $description = "<text>{$this->shell->name}</text>";
        }
        $this->out($description);
        $this->out('');
        
        /**
         * bin/console cron - runs main method
         * bin/console something - runs something method
         * bin/console noExists - if command set and does not exist
         */

        $method = null;
        if ($this->args) {
            $method = array_shift($this->args);
        }
        
        if ($method === null and method_exists($this->shell, 'main')) {
            $method = 'main';
        }
        
        /**
         * Show help and list of subcommands available
         */
        if ($method === null or $method ==='--help' or $method ==='-h') {
            $method = 'help';
        }
    
        try {
            if (! $this->shell->runCommand($method, $this->args)) {
                $this->shell->help();
            }
        } catch (StopExecutionException $ex) {
            return false;
        }
        return true;
    }

    /**
     * Gets the shell used by the dispatcher
     *
     * @return \Origin\Console\Shell
     */
    public function shell()
    {
        return $this->shell;
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
            $shells[$plugin] = $this->scandir(PLUGINS . DS . Inflector::underscore($plugin) . DS . 'src' . DS . 'Console');
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
}
