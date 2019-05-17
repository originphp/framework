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

namespace Origin\Console\Task;

use Origin\Console\Shell;
use Origin\Core\ConfigTrait;

class Task
{
    use ConfigTrait;

    /**
     * Holds the shell object
     *
     * @var \Origin\Console\Shell
     */
    protected $shell = null;
    /**
     * Holds the Task Registry
     *
     * @var \Origin\Console\Task\TaskRegistry
     */
    protected $taskRegistry = null;
    
    /**
     * Array of tasks and config. This built during construct using $tasks
     *
     * @var array
     */
    protected $_tasks = [];

    public function __construct(Shell $shell, array $config =[])
    {
        $this->taskRegistry = $shell->taskRegistry();

        $this->config($config);
        $this->initialize($config);
    }

    /**
     * Handle lazy loading
     */
    public function __get($name)
    {
        if (isset($this->_tasks[$name])) {
            $this->{$name} = $this->taskRegistry()->load($name, $this->_tasks[$name]);
       
            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
    }
    /**
     * Sets another Task to be loaded within this Task. Tasks here
     * will be lazy loaded when needed. Startup/shutdown callbacks
     * will not be called for tasks loaded within tasks.
     *
     * @param string $task
     * @param array $config
     * @return \Origin\Console\Task\Task
     */
    public function loadTask(string $name, array $config = [])
    {
        list($plugin, $task) = pluginSplit($name); // split so we can name properly
        $config = array_merge(['className' => $name . 'Task','enable'=>false], $config);
        $this->_tasks[$task] = $config;
    }

    /**
     * This is called when task is loaded for the first time
     */
    public function initialize(array $config)
    {
    }

    /**
     * This called after the shell startup but before the shell method.
     */
    public function startup()
    {
    }

    /**
     * This is called after the shell method but before the shell shutdown
     */
    public function shutdown()
    {
    }

    /**
     * Returns the current shell where the task is loaded
     *
     * @return \Origin\Console\Shell
     */
    public function shell()
    {
        return $this->taskRegistry()->shell();
    }

    /**
    * Gets the TaskRegistry
    *
    * @return \Origin\Console\Task\TaskRegistry
    */
    public function taskRegistry()
    {
        return $this->taskRegistry;
    }


    /**
         * Outputs to the console text
         *
         * @param string|Array $data
         * @param boolean $newLine
         * @return void
         */
    public function out($data, $newLine = true)
    {
        $this->shell()->out($data, $newLine);
    }
}
