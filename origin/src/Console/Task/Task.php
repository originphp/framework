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

namespace Origin\Console\Task;

use Origin\Console\Shell;
use Origin\Core\ConfigTrait;

class Task
{
    use ConfigTrait;

    /**
     * Holds the shell object
     *
     * @var Shell
     */
    protected $shell = null;
    /**
     * Holds the Task Registry
     *
     * @var TaskRegistry
     */
    protected $registry = null;
    

    public function __construct(Shell $shell, array $config =[])
    {
        $this->registry = $shell->registry;

        $this->config($config);
        $this->initialize($config);
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
     * This is called after the shell method
     */
    public function shutdown()
    {
    }

    /**
     * Returns the current shell where the task is loaded
     *
     * @return void
     */
    public function shell()
    {
        return $this->registry->shell();
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
        $this->shell()->out($data, $newLine);
    }
}
