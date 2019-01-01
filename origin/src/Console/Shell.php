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
use Origin\Console\Task\TaskRegistry;

use ReflectionClass;
use ReflectionMethod;

class Shell
{
    /**
     * Name of this shell
     *
     * @var [type]
     */
    protected $name = null;

    /**
     * Inject request and response
     *
     * @param array $arguments
     * @param ConsoleOutput $consoleOutput
     * @return void
     */
    public function __construct(array $arguments =[], ConsoleOutput $consoleOutput)
    {
        $this->args = $arguments;
        $this->consoleOutput = $consoleOutput;

        list($namespace, $this->name) = namespaceSplit(get_class($this));
     
        $this->registry = new TaskRegistry($this);

        $this->initialize($arguments);
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
        $this->consoleOutput->write($data);
    }

   
    /**
    * Loads a model, uses from registry or creates a new one.
    *
    * @param string $model
    *
    * @return Model
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
     */
    public function loadTask(string $name, array $config = [])
    {
        $config = array_merge(['className' => $name.'Task'], $config);
        $this->{$name} = $this->registry->load($name, $config);
    }

    /**
     * Loads multiple tasks
     *
     * @param array $tasks
     * @return void
     */
    public function loadTasks(array $tasks)
    {
        foreach ($tasks as $name => $config) {
            if (is_int($name)) {
                $name = $config;
                $config = [];
            }
            $this->loadTask($name, $config);
        }
    }

    public function startupProcess()
    {
        $this->startup();
        $this->registry->call('startup');
    }

    public function shutdownProcess()
    {
        $this->shutdown();
        $this->registry->call('shutdown');

        //# Free Mem for no longer used items
        foreach ($this->registry->loaded() as $task) {
            unset($this->{$task});
        }
        $this->registry->clear();
        unset($this->registry);
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
        $reflection = new ReflectionMethod($this, $method);
        return $reflection->isPublic();
    }
}
