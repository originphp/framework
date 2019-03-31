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

use Origin\Console\Exception\StopExecutionException;

use Origin\Console\Exception\ConsoleException;

use Origin\Console\ConsoleInput;
use Origin\Console\ConsoleOutput;
use Origin\Console\Task\TaskRegistry;
use Origin\Model\ModelRegistry;
use ReflectionClass;
use ReflectionMethod;

use Origin\Model\Exception\MissingModelException;

class Shell
{
    /**
     * Name of this shell
     *
     * @var string
     */
    public $name = null;

    /**
     * Holds the task registry
     *
     * @var \Origin\Console\Task\TaskRegistry
     */
    protected $taskRegistry = null;

    /**
     * Holds the console Output Object
     *
     * @var \Origin\Console\ConsoleOutput
     */
    public $output = null;

    /**
     * Holds the console input Resource
     *
     * @var ConsoleInput
     */
    public $input = null;

    /**
     * Holds the arguments (after being parsed)
     *
     * @var array
     */
    public $args = [];
    /**
     * This holds the parmams that were parsed from argv
     *
     * @var array
     */
    public $params = [];

    /**
     * Holds the description for this shell. This is displayed
     * during help.
     *
     * @var string
     */
    public $description = '';

    /**
     * Holds the options for this shell
     */

    public $options = [
       // 'help'=>['name'=>'help','help'=>'Displays this help.','short'=>'h']
    ];

    /**
     * Holds the commands for this shell
     *
     * @var array
     */
    public $commands = [];

    /**
     * Inject request and response
     *
     * @param array $arguments
     * @param \Origin\Console\ConsoleOutput $consoleOutput
      * @param \Origin\Console\ConsoleInput $consoleInput
     * @return void
     */
    public function __construct(ConsoleOutput $consoleOutput, ConsoleInput $consoleInput)
    {
        $this->output = $consoleOutput;
        $this->input = $consoleInput;

        list($namespace, $this->name) = namespaceSplit(str_replace('Shell', '', get_class($this)));
        
        $this->taskRegistry = new TaskRegistry($this);
    }

    /**
     * Runs the command on this shell, this is called by the shellDispatcher
     *
     * @param string $name
     * @param array $args
     * @return void
     */
    public function runCommand(string $name, array $args)
    {
        $this->initialize();
        $this->parseArguments($args);

        // If no commands setup then allow any accesible method
        if (empty($this->commands)) {
            if (!$this->isAccessible($name)) {
                return false;
            }
        }
        // if commands are setup, then restrict to only those
        if ($name !=='main' and empty($this->commands) === false and !isset($this->commands[$name])) {
            return false;
        }

        $this->startupProcess();
        $this->{$name}();
        $this->shutdownProcess();
        
        return true;
    }

    /**
     * A Simple argument parser which populates the $args and $params based
     * upon standards.
     *
     * --save  (the value will true)
     * --datasource=test
     * @todo in future, setting up defaults will automatically show help and allow for short optiosn e.g -d which is short for --datasource
     * @param array $arguments
     * @return void
     */
    protected function parseArguments(array $arguments)
    {
        $map = [];
    
        // Create map for shorts
        foreach ($this->options as $option) {
            if ($option['short']) {
                $map[$option['short']] = $option['name'];
            }
        }
  
        foreach ($arguments as $arg) {
            // Commands
            if ($arg[0] !== '-') {
                $this->args[] = $arg;
                continue;
            }

            // Extract value
            $value = true;
            $option = $arg;
            if (strpos($arg, '=') !== false) {
                list($option, $value) = explode('=', $arg);
            }
       
            // Convert short to long
            if (substr($option, 0, 2) !== '--') {
                $key = substr($option, 1);
                if (isset($map[$key])) {
                    $option  = '--' .  $map[$key];
                }
            }
            $param = substr($option, 2); // remove --
      
            if (($value !== true and empty($this->options[$param]['value'])) or ($value === true and !empty($this->options[$param]['value']))) {
                $this->error('Invalid Argument', 'The argument ' . $arg. ' is not valid');
            }
        
            if (!isset($this->options[$param])) {
                $this->error('Invalid Argument', 'The argument ' . $arg. ' is not valid');
            }
            $this->params[$param] = $value;
        }
    }

    /**
     * Called when the Shell is constructed
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Called before the shell method is called
     *
     * @return void
     */
    public function startup()
    {
    }
    
    /**
     * Called after the shell method is called
     *
     * @return void
     */
    public function shutdown()
    {
    }

    /**
     * Outputs to the console text
     *
     * @param string $data
     * @param boolean $newLine
     * @return void
     */
    public function out(string $data, $newLine = true)
    {
        if ($newLine) {
            $data .= "\n";
        }
        $this->output->write($data);
    }

    /**
     * Reads input from the console, use for prompts
     *
     * @param string $prompt The question to ask
     * @param array $options ['yes','no']
     * @param string $default default value if user presses enter
     * @return void
     */
    public function in(string $prompt, array $options=[], string $default = null)
    {
        $input =  $defaultString = '';
        $optionsString = implode('/', $options);
        if ($default) {
            $defaultString = "[{$default}]";
        }
       
        // Check both uppercase and lower case input
        $options = array_merge(
            array_map('strtolower', $options),
            array_map('strtoupper', $options)
        );

        while ($input === '' || !in_array($input, $options)) {
            $this->out("<prompt>{$prompt}</prompt> ({$optionsString}) {$defaultString}");
            $input = $this->input->read();
            if ($input === '' and $default) {
                return $default;
            }
        }
        return $input;
    }

   
    /**
    * Loads a model, uses from registry or creates a new one.
    *
    * @param string $model
    *
    * @return \Origin\Model\Model
    */
    public function loadModel(string $model)
    {
        if (isset($this->{$model})) {
            return $this->{$model};
        }

        $this->{$model} = ModelRegistry::get($model);

        if ($this->{$model}) {
            return $this->{$model};
        }
        throw new MissingModelException($model);
    }

    /**
     * Loads a Task for use with the shell
     *
     * @param string $name  Shell task name
     * @param array  $config array of config to be passed to shell task. Class name
     * @return \Origin\Console\Task\Task
     */
    public function loadTask(string $name, array $config = [])
    {
        list($plugin, $task) = pluginSplit($name); // split so we can name properly
        $config = array_merge(['className' => $name . 'Task'], $config);
        $this->{$task} = $this->taskRegistry()->load($name, $config);
        return $this->{$task};
    }

    public function startupProcess()
    {
        $this->startup();
        $this->taskRegistry()->call('startup');
    }

    public function shutdownProcess()
    {
        $this->taskRegistry()->call('shutdown');
        $this->shutdown();

        //# Free Mem for no longer used items
        $this->taskRegistry()->destroy();
        unset($this->taskRegistry);
    }


    /**
    * Checks if an action on this shell is accesible
    *
    * @param string $action
    *
    * @return bool
    */
    public function isAccessible(string $method)
    {
        $shell = new ReflectionClass('Origin\Console\Shell');
        if ($shell->hasMethod($method)) {
            return false;
        }
        if (!method_exists($this, $method)) {
            return false;
        }
        $reflection = new ReflectionMethod($this, $method);
        return $reflection->isPublic();
    }

    /**
     * Gets the task registry object
     *
     * @return \Origin\Console\Task\TaskRegistry
     */
    public function taskRegistry()
    {
        return $this->taskRegistry;
    }

    /**
     * Sets/gets the params
     *
     * @param string|array|null $key
     * @param mixed $value
     * @return array|string|null
     */
    public function params($key = null, $value=null)
    {
        if ($key === null) {
            return $this->params;
        }
        if (is_array($key)) {
            return $this->params = $key;
        }
        if (func_num_args() === 2) {
            return $this->params[$key] = $value;
        }
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        return null;
    }

    /**
     * Gets or sets the args
     *
     * @param integer $index
     * @return mixed|null
     */
    public function args(int $index = null)
    {
        if ($index === null) {
            return $this->args;
        }
        if (isset($this->args[$index])) {
            return $this->args[$index];
        }
        return null;
    }

    /**
     * Adds an available option
     *
     * @param string $name
     * @param array $options Options include help:help text short: short option e.g -ds
     * @return void
     */
    public function addOption(string $name, array $options=[])
    {
        $options += ['name'=>$name,'short'=>null,'help'=>null];
        $this->options[$name] = $options;
    }

    /**
     * Adds a available
     *
     * @param string $name
     * @param array $options
     * @return void
     */
    public function addCommand(string $name, array $options=[])
    {
        $options += ['name'=>$name,'help'=>null];
        $this->commands[$name] =  $options;
    }

    public function help()
    {
        if ($this->description) {
            $this->out($this->description);
            $this->out('');
        }
        $this->out('<info>Usage:</info>');
        $this->out('  command [options] [arguments]');
        $this->out('');
        if ($this->options) {
            $options = [];
            $this->out('<info>Options:</info>');
            foreach ($this->options as $option) {
                $value = '';
                if (!empty($option['value'])) {
                    $value  = '=' . $option['value'];
                }
                $text = '--' . $option['name'] . $value;
               
                if ($option['short']) {
                    $text = '-'. $option['short'] . ', ' . $text;
                }

                $options[$text] = $option['help'];
            }
            $this->plotTable($options);
            $this->out('');
        }
        if ($this->commands) {
            $this->out('<info>Available Commands:</info>');
            $options = [];
            foreach ($this->commands as $command) {
                $options[$command['name']] = $command['help'];
            }
            $this->plotTable($options);
            $this->out('');
        }
    }

    /**
     * Draws the options/commands table
     *
     * @param array $data
     * @return void
     */
    private function plotTable(array $data)
    {
        $maxLength = 0;
        foreach ($data as $key => $value) {
            if (strlen($key)> $maxLength) {
                $maxLength = strlen($key);
            }
        }
        foreach ($data as $key => $value) {
            $key = str_pad($key, $maxLength);
            $this->out("  {$key}\t{$value}");
        }
    }

    /**
     * Styles an error message and then exits the script
     *
     * @param string $message
     * @return void
     */
    public function error(string $title, string $message=null)
    {
        $this->output->error($title, $message);
        $this->stop($title);
    }

    /**
     * Wrapper for exiting the shell script
     *
     * @param [type] $status
     * @return void
     */
    protected function stop(string $title='Console stopped')
    {
        throw new StopExecutionException($title);
    }
}
