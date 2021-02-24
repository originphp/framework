<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Schedule\Command;

use Origin\Schedule\Schedule;
use Origin\Console\Command\Command;
use Origin\Schedule\Exception\ScheduleException;

class ScheduleRunCommand extends Command
{
    protected $name = 'schedule:run';
    protected $description = 'Runs the scheduled tasks';

    protected function initialize(): void
    {
        $this->addOption('directory', [
            'description' => 'The directory where the tasks files are'
        ]);

        $this->addOption('id', [
            'description' => 'A specific event ID that should be run'
        ]);
    }
    
    /**
     * @return void
     */
    protected function execute(): void
    {
        $path = $this->options('directory') ?: Schedule::config('path');

        if (is_null($path)) {
            $path = (defined('ROOT') ? ROOT : getcwd()) . '/app/Task';
        }

        try {
            Schedule::run($path, $this->options('id'));
        } catch (ScheduleException $exception) {
            $this->throwError($exception->getMessage());
        }
    }
}
