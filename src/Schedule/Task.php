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
namespace Origin\Schedule;

use Origin\Core\HookTrait;

abstract class Task
{
    use HookTrait;

    /**
     * Name of this task
     *
     * @var string
     */
    protected $name;

    /**
     * Task description which will be used by the command
     *
     * @var string
     */
    protected $description = null;

    public function __construct()
    {
        list($namespace, $name) = namespaceSplit(get_class($this));
        $this->name = $this->name ?? $name;

        $this->executeHook('initialize', func_get_args());
    }

    /**
     * Schedule your task here
     *
     * @param \Origin\Schedule\Schedule $schedule
     * @return void
     */
    abstract protected function handle(Schedule $schedule): void;

    /**
     * Dispatches the task
     *
     * @return void
     */
    public function dispatch(): void
    {
        $schedule = new Schedule();
        $this->executeHook('startup');
        $this->handle($schedule);
        $schedule->dispatch();
        $this->executeHook('shutdown');
    }
}
