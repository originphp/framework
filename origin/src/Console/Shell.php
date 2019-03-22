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
use Origin\Console\Task\TaskRegistry;
use Origin\Model\ModelRegistry;
use Origin\Core\Logger;

use ReflectionClass;
use ReflectionMethod;

use Origin\Model\Exception\MissingModelException;

class Shell
{
    /**
     * Name of this shell
     *
     * @var [string
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
     * Inject request and response
     *
     * @param array $arguments
     * @param \Origin\Console\ConsoleOutput $consoleOutput
      * @param \Origin\Console\ConsoleInput $consoleInput
     * @return void
     */
    public function __construct(array $arguments =[], ConsoleOutput $consoleOutput, ConsoleInput $consoleInput)
    {
        $this->parseArguments($arguments);
        $this->output = $consoleOutput;
        $this->input = $consoleInput;

        list($namespace, $this->name) = namespaceSplit(get_class($this));
     
        $this->taskRegistry = new TaskRegistry($this);

        $this->initialize($arguments);
    }

    /**
     * A Simple argument parser which populates the $args and $parms
     *
     * -save  (the value will true)
     * -datasource=test
     * @param array $arguments
     * @return void
     */
    protected function parseArguments(array $arguments)
    {
        foreach ($arguments as $arg) {
            if ($arg[0]==='-') {
                $value = true;
                $param = substr($arg, 1);
                if (strpos($param, '=') !== false) {
                    list($param, $value) = explode('=', $param);
                }
                $this->params[$param] = $value;
            } else {
                $this->args[] = $arg;
            }
        }
    }

    /**
     * Called when the Shell is constructed
     *
     * @param array $arguments from cli
     * @return void
     */
    public function initialize(array $arguments)
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
     * @param string $prompt what
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
     * Returns a Logger Object
     *
     * @param string $channel
     * @return \Origin\Core\Logger
     */
    public function logger(string $channel = 'Shell')
    {
        return new Logger($channel);
    }

    /**
     * Sets/gets the params
     *
     * @param string|array|null $key
     * @param mixed $value
     * @return array|string|null
     */
    public function params($key = null, $value=null){
        if($key === null){
            return $this->params;
        }
        if(is_array($key)){
            return $this->params = $key;
        }
        if(func_num_args() === 2){
            return $this->params[$key] = $value;
        }
        if(isset($this->params[$key])){
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
    public function args(int $index = null){
        if($index === null){
            return $this->args;
        }
        if(isset($this->args[$index])){
            return $this->args[$index];
        }
        return null;
    }

}
