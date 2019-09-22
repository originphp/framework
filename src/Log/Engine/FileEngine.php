<?php
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

class FileEngine extends BaseEngine
{
    /**
     * Default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'file' => null,
        'path' => LOGS,
        'levels' => [],
        'channels' => [],
    ];

    public function initialize(array $config)
    {
        /**
         * Undone deprecation mistake
         */
        if (isset($this->config['filename'])) {
            $this->config['file'] = $this->config['filename'];
        }
        if ($this->config('file') === null) {
            $this->config('file', 'application.log');
        }
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
        $message = $this->format($level, $message, $context) . "\n";
        $file = $this->config('path') . DS . $this->config('filename');

        return (bool) file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
    }
}
