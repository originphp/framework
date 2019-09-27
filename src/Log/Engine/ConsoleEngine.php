<?php
declare(strict_types = 1);
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Log\Engine;

use Origin\Console\ConsoleOutput;

class ConsoleEngine extends BaseEngine
{
    /**
     * Holds the ConsoleOutput
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $output = null;

    /**
      * Default configuration
      *
      * @var array
      */
    protected $defaultConfig = [
        'stream' => 'php://stderr',
        'levels' => [],
        'channels' => [],
    ];

    public function initialize(array $config) : void
    {
        $this->output = new ConsoleOutput($this->config('stream'));
    }

    /**
      * Workhorse for the logging methods
      *
      * @param string $level e.g debug, info, notice, warning, error, critical, alert, emergency.
      * @param string $message 'this is a {what}'
      * @param array $context  ['what'='string']
      * @return bool
      */
    public function log(string $level, string $message, array $context = [])
    {
        $message = $this->format($level, $message, $context);

        return (bool) $this->output->write("<{$level}>{$message }</{$level}>");
    }
}
