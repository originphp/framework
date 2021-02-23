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
     * @var \Origin\Schedule\Schedule
     */
    protected $schedule;

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

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
        
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
     * Invokes this task
     *
     * @return void
     */
    public function __invoke(): void
    {
        $this->handle($this->schedule);
    }

    /**
     * Dispatches the Task
     *
     * @return void
     */
    public function dispatch(): void
    {
        $this->executeHook('startup');
        $this->handle($this->schedule);
        $this->schedule->dispatch();
        $this->executeHook('shutdown');
    }

    /**
     * Gets the schedule object for this task
     *
     * @return \Origin\Schedule\Schedule
     */
    public function schedule(): Schedule
    {
        return $this->schedule;
    }
}
