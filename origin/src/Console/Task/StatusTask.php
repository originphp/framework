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

 /**
  * This will be depreciated. This is more helper as it is for output.
  */
namespace Origin\Console\Task;

class StatusTask extends Task
{
    public function initialize(array $config)
    {
        deprecationWarning('Status Task is being depreciated. Use shell::status');
    }

    public function ok(string $message)
    {
        return $this->custom('OK', 'green', $message);
    }
    public function error(string $message)
    {
        return $this->custom('ERROR', 'red', $message);
    }
    public function custom(string $status, string $color, string $message)
    {
        $this->out("<white>[</white> <{$color}>{$status}</{$color}> <white>]</white> <white>{$message}</white>");
    }
}
